<?php

/**
 * @package redaxo\export_cleanup
 */
class rex_cronjob_export_cleanup extends rex_cronjob
{

    
    private function datediffCalc($dateA, $dateB = 0)
	{
	
	    if ($dateA > $dateB) {
	        $dateA ^= $dateB ^= $dateA ^= $dateB;
	    }
	
	    $refTimeA_d = date('d', $dateA);
	    $refTimeA_m = date('m', $dateA);
	    $refTimeA_y = date('Y', $dateA);
	
	    $refTimeB_d = date('d', $dateB);
	    $refTimeB_m = date('m', $dateB);
	    $refTimeB_y = date('Y', $dateB);
	
	    $diffTime_d = $refTimeB_d - $refTimeA_d;
	
	    if ($diffTime_d < 0) {
	        $diffTime_d += date('t', $dateA);
	        $memC = 1;
	    } else {
	        $memC = 0;
	    }
	
	    $diffTime_m = $refTimeB_m - $refTimeA_m - $memC;
	
	    if ($diffTime_m < 0) {
	        $diffTime_m += 12;
	        $memC = 1;
	    } else {
	        $memC = 0;
	    }
	
	    $diffTime_y = $refTimeB_y - $refTimeA_y - $memC;
	
	    return ['Y' => $diffTime_y, 'm' => $diffTime_m, 'd' => $diffTime_d];
	}
    
    public function execute()
    {
		
		$time = time();
		//$time = strtotime('+52 month +1 week + 2 day');

		
		$refTimeHour = strtotime(date('Y-m-d H:00',$time));
		$refTimeDay = strtotime(date('Y-m-d',$time));

		$filenameContains = $this->getParam('filename_contains', "");
		
        $dir = rex_backup::getDir();

        $backupFilesToDelete = [];
        $backupFiles = rex_backup::getBackupFiles('.sql'); // .tar.gz for files


        foreach ($backupFiles as $backupFile) {
	        if (!$filenameContains || (strpos($backupFile, $filenameContains) !== false)) {
		        $filetime = filemtime($dir . '/' . $backupFile);
		        
		        $dateDiff = $this->datediffCalc($filetime, $refTimeDay);
		        
		        $backupFilesToDelete[] = [	"file"			=> $backupFile,
		        							"time"			=> $filetime,
		        							"date"			=> date('Y-m-d H:i:s', $filetime),
		        							"diff-h"		=> intval(($refTimeHour - $filetime) / 3600),
		        							"diff-d"		=> intval(($refTimeDay - $filetime) / 86400),
		        							"diff-m"		=> $dateDiff['m'] + $dateDiff['Y'] * 12,
		        							"diff-y"		=> $dateDiff['Y'],
		        						
		        						];
		    }
        	
        }
        
        // check files by filter and mark to delete
        
        foreach (['h', 'd', 'm', 'y'] as $paramStr) { 

	        $keepParam = $this->getParam('keep_' . $paramStr , 0);
	        
	        if($keepParam){
		        
		        $keepArr = [];
		        
		        foreach ($backupFilesToDelete as $key => $backupFileToDelete) {
			        
			        if(!isset($backupFilesToDelete[$key]['delete'])){
				        
						$value = $backupFileToDelete['diff-' . $paramStr ];

				        if($value > $keepParam ){
					        
					        if(in_array($value, $keepArr)){
						        $backupFilesToDelete[$key]['delete'] = $paramStr;
					        }else{
						        $keepArr[] = $value;
						    }
					        
					    }
				        
				    }
					

			        
			        
		        }
		        
		        
		    }
        
        }
        


        //	debug
        //	echo '<pre class="dbgPre">' . print_r($backupFilesToDelete,1) . '</pre>';
        
        
        // delete marked files
        
        $log = [];
        
        foreach ($backupFilesToDelete as $key => $backupFileToDelete) {
	        
	        if(isset($backupFilesToDelete[$key]['delete'])){
		        
		        $succ = rex_file::delete($dir . '/' . $backupFileToDelete['file']);
		        
		        $log[] = $backupFileToDelete['file'] . " " . rex_i18n::msg('export_cleanup_deleted') . " (" . rex_i18n::msg('export_cleanup_label_p_' . $backupFilesToDelete[$key]['delete'] ) . ")" ;
		        
		        
		        if (!$succ){
	   				
	   				$log[] = "failed!" ;
	   				$this->setMessage(implode(", \n", $log));
	   				return false;
	   			}
		    } 
	    }
        
		if($log){
			$this->setMessage(implode(", \n", $log));
		}else{
			$this->setMessage(rex_i18n::msg('export_cleanup_nothing_to_delete'));
		}
        
        
        return true;
        

    }

    public function getTypeName()
    {
        return rex_i18n::msg('export_cleanup_title');
    }

    public function getParamFields()
    {
        $fields = [
            [
                'label' => rex_i18n::msg('export_cleanup_filename_contains'),
                'name' => 'filename_contains',
                'type' => 'text',
                'default' => "",
                'notice' => rex_i18n::msg('export_cleanup_filename_contains_notice'),
            ],
            [
                'label' => rex_i18n::msg('export_cleanup_label_p_h'),
                'name' => 'keep_h',
                'type' => 'select',
                'options' => [	0 => rex_i18n::msg('export_cleanup_opt_ign'),
                				1 => rex_i18n::msg('export_cleanup_p_h_sing'),
                				3 => str_replace('%n', '3', rex_i18n::msg('export_cleanup_p_h_plur')),
                				6 => str_replace('%n', '6', rex_i18n::msg('export_cleanup_p_h_plur')),
                				12 => str_replace('%n', '12', rex_i18n::msg('export_cleanup_p_h_plur')),
                				24 => str_replace('%n', '24', rex_i18n::msg('export_cleanup_p_h_plur')),
                				48 => str_replace('%n', '48', rex_i18n::msg('export_cleanup_p_h_plur'))               			
                			],
            ],
            [
                'label' => rex_i18n::msg('export_cleanup_label_p_d'),
                'name' => 'keep_d',
                'type' => 'select',
                'options' => [	0 => rex_i18n::msg('export_cleanup_opt_ign'),
                				1 => rex_i18n::msg('export_cleanup_p_d_sing'),
                				2 => str_replace('%n', '2', rex_i18n::msg('export_cleanup_p_d_plur')),
                				3 => str_replace('%n', '3', rex_i18n::msg('export_cleanup_p_d_plur')),
                				4 => str_replace('%n', '4', rex_i18n::msg('export_cleanup_p_d_plur')),
                				5 => str_replace('%n', '5', rex_i18n::msg('export_cleanup_p_d_plur')),
                				6 => str_replace('%n', '6', rex_i18n::msg('export_cleanup_p_d_plur')),
                				7 => str_replace('%n', '7', rex_i18n::msg('export_cleanup_p_d_plur')),
                				14 => str_replace('%n', '14', rex_i18n::msg('export_cleanup_p_d_plur')),
                				30 => str_replace('%n', '30', rex_i18n::msg('export_cleanup_p_d_plur')),
                				60 => str_replace('%n', '60', rex_i18n::msg('export_cleanup_p_d_plur')),
                				120 => str_replace('%n', '120', rex_i18n::msg('export_cleanup_p_d_plur'))
                			
                			],
            ],
            [
                'label' => rex_i18n::msg('export_cleanup_label_p_m'),
                'name' => 'keep_m',
                'type' => 'select',
                'options' => [	0 => rex_i18n::msg('export_cleanup_opt_ign'),
                				1 => rex_i18n::msg('export_cleanup_p_m_sing'),
                				2 => str_replace('%n', '2', rex_i18n::msg('export_cleanup_p_m_plur')),
                				3 => str_replace('%n', '3', rex_i18n::msg('export_cleanup_p_m_plur')),
                				6 => str_replace('%n', '6', rex_i18n::msg('export_cleanup_p_m_plur')),
                				12 => str_replace('%n', '12', rex_i18n::msg('export_cleanup_p_m_plur')),
                				24 => str_replace('%n', '24', rex_i18n::msg('export_cleanup_p_m_plur'))
                			
                			],
            ],
            [
                'label' => rex_i18n::msg('export_cleanup_label_p_y'),
                'name' => 'keep_y',
                'type' => 'select',
                'options' => [	0 => rex_i18n::msg('export_cleanup_opt_ign'),
                				1 => rex_i18n::msg('export_cleanup_p_y_sing'),
                				2 => str_replace('%n', '2', rex_i18n::msg('export_cleanup_p_y_plur')),
                				3 => str_replace('%n', '3', rex_i18n::msg('export_cleanup_p_y_plur')),
                				4 => str_replace('%n', '4', rex_i18n::msg('export_cleanup_p_y_plur')),
                				5 => str_replace('%n', '5', rex_i18n::msg('export_cleanup_p_y_plur')),
                				10 => str_replace('%n', '10', rex_i18n::msg('export_cleanup_p_y_plur'))
                			
                			],
            ],
        ];

        return $fields;
    }
}
