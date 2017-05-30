# Magento 2 Product Viewer



## Install from CLI
``` 
cd /path/to/site??????????
composer config repositories.salesgrid path vcs https://github.com/joshspivey/magento2-product-viewer.git 
composer require JoshSpivey/mage2-product-viewer
php -f bin/magento setup:upgrade
php bin/magento setup:di:compile
sudo php bin/magento setup:static-content:deploy && sudo php bin/magento indexer:reindex && sudo php bin/magento cache:clean && sudo php bin/magento cache:flush
```


## Please Contribute if you want to add any of this functionality I will merge to master. 
