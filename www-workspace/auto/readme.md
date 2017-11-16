## picili - auto
sync and process files from dropbox and instragram


### commands

process-dropbox-files  
elastic-create  
elastic-delete  
file-tagger  
index-all  
process  
process-all  
pull-dropbox  
seed  

###

migrations:
```
php artisan migrate
php artisan migrate --path="../picili-shared/Migrations"
```

seed elastic test data
- `php artisan db:seed --class=PiciliFileElasticSeeder`

### requires

[run physical folder seeder]
php mongo extension
cacert for aws. on windows add to php.ini: 'curl.cainfo = C:/cacert.pem' (put correct pem file in)
