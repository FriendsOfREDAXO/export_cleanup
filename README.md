# export_cleanup
Erweiterung für cronjob+backup um ältere Exporte dynamisch zu löschen

Export Cleanup AddOn für Redaxo 5
=================================

Benötigt folgende AddOns
- backup > 2.0.3
- cronjob > 2.1.0

Nachdem das AddOn installiert & aktiviert wurde, steht bei den Cronjobs eine neuer Typ "Export Cleanup" zur Verfügung. Damit können ältere Backups nach dynamischen Vorgaben automatisch wieder gelöscht werden. Wenn z.B. mit dem Typ "Datenbankexport" automatische Backups erstellt werden, kann damit der Speicherbedarf reduziert/optimiert werden. Damit sind stündliche oder noch häufigere Backups leicht zu bewerkstelligen.
