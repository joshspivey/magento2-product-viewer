<?php
 
namespace JoshSpivey\ProductViewer\Controller\Products;
 
class Products extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory  */

    protected $searchCriteriaBuilder;
    protected $filterBuilder;
    protected $resultJsonFactory;
    protected $sortOrderBuilder;
    protected $filterGroupBuilder;
    protected $filterGroup;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Api\SearchCriteriaInterface $criteria,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
    ){

        $this->productRepository = $productRepository;
        $this->searchCriteria = $criteria;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterGroup = $filterGroup;
        $this->filterBuilder = $filterBuilder;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        $this->sortOrderBuilder = $sortOrderBuilder;
        parent::__construct($context);
    }

    public function execute()
    {
      header('Content-Type: application/json');
      echo json_encode($this->getProductData(),  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
      //use json encode until they fix the number type casting in the mage 2 json factory
      //see issue for updates https://github.com/magento/magento2/issues/8244
    }


    protected function getProductData()
    {

        $perPage = $this->getRequest()->getParam('results_per_page');
        $page = $this->getRequest()->getParam('page');
        $orderBy = ($this->getRequest()->getParam('order'))? $this->getRequest()->getParam('order') : 'DESC';
        $keyword = $this->getRequest()->getParam('keyword');
        $productSearch = $this->getRequest()->getParam('productSearch');
        $searchColumns = $this->getRequest()->getParam('searchColumns');
        $lowRange = $this->getRequest()->getParam('low');
        $highRange = $this->getRequest()->getParam('high');

        if($lowRange < 0){
            return 'Please select a number higher than 0.';
        }else if($highRange - $lowRange < 500){
            return 'The value must be more then 500 of the starting value.';
        }else{
        
          $sortOrder = $this->sortOrderBuilder
              ->setField('name')
              ->setDirection($orderBy)
              ->create();

          $filters[] = $this->filterBuilder
                  ->setField('status')
                  ->setConditionType('in')
                  ->setValue($this->productStatus->getVisibleStatusIds())
                  ->create();

          $filters[] = $this->filterBuilder
                  ->setField('visibility')
                  ->setConditionType('in')
                  ->setValue($this->productVisibility->getVisibleInSiteIds())
                  ->create();
          
          $filters[] = $this->filterBuilder
                  ->setField('price')
                  ->setConditionType('lteq')
                  ->setValue($highRange)
                  ->create();

          $filters[] = $this->filterBuilder
                  ->setField('price')
                  ->setConditionType('gteq')
                  ->setValue($lowRange)
                  ->create();

          $this->searchCriteria = $this->searchCriteriaBuilder
                  ->setPageSize($perPage)
                  ->setCurrentPage($page)
                  ->addSortOrder($sortOrder)
                  ->addFilters($filters)
                  ->create();

          $products = $this->productRepository->getList($this->searchCriteria);
          $productItems = $products->getItems();

          $returnArray = [];
          $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
          $store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
          foreach($productItems as $product)
          {
            $productStockObj = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($product->getId());

            array_push($returnArray, [
              "name"=>$product->getName(), 
              "sku"=>$product->getSku(), 
              "qty"=>$productStockObj->getQty(), 
              "thumb" => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' .$product->getData('thumbnail'), 
              "url" => $product->getProductUrl() 
            ]);
          }

          return $returnArray;
        }
    }


}