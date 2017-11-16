## picili user api


## before Testing

seed the correct data, via the auto processor..

php artisan elastic-delete
php artisan elastic-create
php artisan db:seed --class=PiciliFileElasticSeeder
