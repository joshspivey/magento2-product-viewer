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
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility
    ){

        $this->productRepository = $productRepository;
        $this->searchCriteria = $criteria;
        $this->filterGroup = $filterGroup;
        $this->filterBuilder = $filterBuilder;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
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
        $sortBy = ($this->getRequest()->getParam('sort_by'))? $this->getRequest()->getParam('sort_by') : 'created_at';
        $orderBy = ($this->getRequest()->getParam('order'))? $this->getRequest()->getParam('order') : 'DESC';
        $keyword = $this->getRequest()->getParam('keyword');
        $productSearch = $this->getRequest()->getParam('productSearch');
        $searchColumns = $this->getRequest()->getParam('searchColumns');

        // $sortOrder = $this->sortOrderBuilder
        //     ->setField($sortBy)
        //     ->setDirection($orderBy)
        //     ->create();

        $this->filterGroup->setFilters([
            $this->filterBuilder
                ->setField('status')
                ->setConditionType('in')
                ->setValue($this->productStatus->getVisibleStatusIds())
                ->create(),
            $this->filterBuilder
                ->setField('visibility')
                ->setConditionType('in')
                ->setValue($this->productVisibility->getVisibleInSiteIds())
                ->create(),
        ]);

        $this->searchCriteria->setFilterGroups([$this->filterGroup])
            // ->addSortOrder($sortOrder)
            ->setPageSize($perPage)
            ->setCurrentPage($page);

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