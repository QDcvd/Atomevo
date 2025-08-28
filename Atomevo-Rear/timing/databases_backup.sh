time=_` date +%Y_%m_%d `
/usr/local/mysql/bin/mysqldump --skip-opt  mol | gzip >/usr/local/mysql/backup/backup$time.sql.gz