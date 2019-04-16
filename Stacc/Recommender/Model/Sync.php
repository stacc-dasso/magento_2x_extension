<?php

namespace Stacc\Recommender\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Stacc\Recommender\Network\Apiclient;
use Stacc\Recommender\Logger\Logger;
use Stacc\Recommender\Network\Environment;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogInventory\Model\Stock\Item;

/**
 * Model Class for Product Syncing
 *
 * Class Stacc_Recommender_Model_Sync
 */
class Sync
{
    /**
     * @var Apiclient
     */
    protected $apiclient;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var ReadHandler
     */
    protected $galleryReadHandler;

    /**
     * @var Status
     */
    protected $productStatus;

    /**
     * @var Visibility
     */
    protected $productVisibility;

    /**
     * @var Item
     */
    protected $stockItem;

    /**
     * @var int
     */
    protected $productsPerPage = 250;

    /**
     * @var int
     */
    protected $curPage = 1;

    /**
     * @var int
     */
    protected $storeId = null;

    /**
     * @var
     */
    protected $app;

    /**
     * @var
     */
    protected $startTime;

    /**
     * @var
     */
    protected $endTime;

    /**
     * @var array
     */
    protected $webIdName = [];

    /**
     * @var array
     */
    protected $catIdName = [];

    /**
     * @var array
     */
    protected $storeCodes = [];

    private $productCollection;

    /**
     * Sync constructor.
     * @param Apiclient $apiclient
     * @param Environment $environment
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ReadHandler $galleryReadHandler
     * @param Status $productStatus
     * @param Visibility $_productVisibility
     * @param Item $stockItem
     */
    public function __construct(
        Apiclient $apiclient,
        Environment $environment,
        StoreManagerInterface $storeManager,
        Logger $logger,
        ProductCollectionFactory $productCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        ReadHandler $galleryReadHandler,
        Status $productStatus,
        Visibility $_productVisibility,
        Item $stockItem
    ) {
        $this->apiclient = $apiclient;
        $this->environment = $environment;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->galleryReadHandler = $galleryReadHandler;
        $this->productStatus = $productStatus;
        $this->productVisibility = $_productVisibility;
        $this->stockItem = $stockItem;
        $this->productCollection = $this->productCollectionFactory->create();
    }

    /**
     * @var array
     */
    protected $response = [];

    /**
     * @return StoreManagerInterface|null
     */
    public function getStoreManager()
    {
        try {
            return $this->storeManager;
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Model/Sync->getStoreManager() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return null;
        }
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param $storeId
     */
    private function setStoreId($storeId)
    {
        if (!($storeId === null)) {
            $this->storeId = $storeId;
        }
    }

    /**
     * Returns Apiclient for sending data
     *
     * @return Apiclient
     */
    public function getApiclient()
    {
        return $this->apiclient;
    }

    /**
     * Returns Logger that will log errors in the functions
     *
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Returns Environment helper that will provide neccessary data
     *
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Sets time when sync was started
     *
     * @param $startTime
     */
    private function setStartTime($startTime)
    {
        if (!($startTime === null)) {
            $this->startTime = $startTime;
        }
    }

    /**
     * Returns time when sync was started
     *
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Sets time when sync was ended
     *
     * @param $endTime
     */
    private function setEndTime($endTime)
    {
        if (!($endTime === null)) {
            $this->endTime = $endTime;
        }
    }

    /**
     * Returns time when sync was ended
     *
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set products amount for page
     *
     * @param $productsPerPage
     */
    public function setProductsPerPage($productsPerPage)
    {
        if ($productsPerPage) {
            $this->productsPerPage = $productsPerPage;
        }
    }

    /**
     * Returns the amount of products per page
     *
     * @return int
     */
    public function getProductsPerPage()
    {
        return $this->productsPerPage;
    }

    /**
     * Set Current page for modified syncing
     *
     * @param $curPage
     */
    private function setCurrentPage($curPage)
    {
        if ($curPage) {
            $this->curPage = $curPage;
        }
    }

    /**
     * Returns currentPage
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->curPage;
    }

    /**
     * Set the list of storeCodes
     *
     * @param $storeCodes
     */
    private function setStoreCodes($storeCodes)
    {
        if (is_array($storeCodes)) {
            $this->storeCodes = $storeCodes;
        }
    }

    /**
     * Returns array of store codes
     *
     * @return array
     */
    public function getStoreCodes()
    {
        return $this->storeCodes;
    }

    /**
     * Maps Web Id to the WebIdNames Array
     *
     * @param $webIdKey
     * @param $webName
     */
    private function setWebIdName($webIdKey, $webName)
    {
        if (!($webIdKey === null) && !($webName === null)) {
            $this->webIdName[$webIdKey] = $webName;
        }
    }

    /**
     * Returns array of Web Names mapped to Id
     *
     * @return array
     */
    public function getWebIdName()
    {
        return $this->webIdName;
    }

    /**
     * Maps Category id to catIdName array
     *
     * @param $catIdKey
     * @param $catName
     */
    private function setCatIdName($catIdKey, $catName)
    {
        if (!($catIdKey === null) && !($catName === null)) {
            $this->catIdName[$catIdKey] = $catName;
        }
    }

    /**
     * Returns array of Category Names mapped to ids
     *
     * @return array
     */
    public function getCatIdName()
    {
        return $this->catIdName;
    }

    /**
     * Sync products catalog, can use storeId and id (product) as parameters to specify sync
     *
     * @param null $storeId
     * @return $this
     */
    public function syncProducts($storeId = null)
    {
        $syncData = ["errors" => 0, "transmitted" => 0, "count" => 0, "pages" => 0];

        try {
            $this->initSync($storeId);

            do {
                // Stop sending if fails 10 times
                if ($syncData["errors"] == 10) {
                    break;
                }

                $syncData = $this->processAndSendPage($syncData);
            } while ($this->getCurrentPage() <= $syncData["pages"]);

            $this->setEndTime(microtime(true));

            $sync_time = $this->getEndTime() - $this->getStartTime();

            $this->logger
                ->notice(
                    "Synchronization finished, took $sync_time seconds, transmitted " .
                    $syncData["transmitted"] . "/" . $syncData["count"] . " products, " .
                    $syncData["errors"] . " errors"
                );
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Model/Sync->syncProducts() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            $syncData["errors"]++;
        }

        return $this;
    }

    /**
     * Setup syncing
     *
     * @param $storeId
     */
    private function initSync($storeId)
    {
        try {
            $this->setStartTime(microtime(true));

            $this->logger->notice("Running products synchronization");

            $this->setStoreId($storeId);
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Model/Sync->initSync() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }
    }

    /**
     * Process products and send them to the API
     *
     * @param $syncData
     * @return mixed
     */
    private function processAndSendPage($syncData)
    {
        try {
            $pageStartTime = microtime(true);

            $currentPage = $this->getCurrentPage();
            $this->logger->info("Sending page " . $currentPage . " started");

            $productCollection = $this->getProductsCollection();

            if ($syncData["count"] == 0) {
                $syncData["count"] = $productCollection->getSize();
                $syncData["pages"] = $productCollection->getLastPageNumber();
            }

            $dataBulk = $this->getModifiedProductsAsBulk($productCollection);

            $data_json = [
                "bulk"       => $dataBulk,
                "properties" => [
                    "current_page"       => $currentPage,
                    "total_pages"        => $syncData["pages"],
                    "amount_of_products" => $syncData["count"],
                    "extension_version"  => $this->getEnvironment()->getVersion(),
                    "store"              => $this->getStoreId()
                ]
            ];
            $syncResponse = $this->getApiclient()->sendProducts($data_json);
            if ($syncResponse != "{}") {
                $this->logger->error("Can't send products", ['error' => (string)$syncResponse]);
                $syncData["errors"]++;
            } else {
                $syncData["transmitted"] += count($dataBulk);
            }
            $this->response["data"] = ["total" => $syncData["count"], "transmitted" => $syncData["transmitted"]];
            $productCollection->clear();

            $pageTime = microtime(true) - $pageStartTime;
            $this->logger->info("Sending page " . $currentPage . " finished, took $pageTime seconds");

            $currentPage++;
            $this->setCurrentPage($currentPage);

            return $syncData;
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Model/Sync->processAndSendPage() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return [];
        }
    }

    /**
     * Create products collection of existing products for processing
     *
     * @return mixed
     */
    private function getProductsCollection()
    {

        try {
            // Build product collection for sync
            $productVisibilityClass = $this->productVisibility;

            if (!($this->getStoreId() === null)) {
                $this->productCollection->setStore($this->getStoreId());
                $this->productCollection->addStoreFilter($this->getStoreId());
            };

            $this->productCollection->addAttributeToFilter(
                'status',
                [
                    'eq' => $this->productStatus->getVisibleStatusIds()
                ]
            );
            $this->productCollection->setVisibility($productVisibilityClass::VISIBILITY_BOTH);
            $this->productCollection->addAttributeToSelect('*');
            $this->productCollection->setFlag('has_stock_status_filter', true);
            $this->productCollection->setOrder('product_id');
            $this->productCollection->setPageSize($this->getProductsPerPage());
            $this->productCollection->setCurPage($this->getCurrentPage());

            return $this->productCollection;
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Model/Sync->getProductsCollection() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
            return [];
        }
    }

    public function getAmountOfPages($storeId = null)
    {
        $this->setStoreId($storeId);
        if ($this->getProductsCollection()) {
            return [
                "products_amount" => $this->getProductsCollection()->getSize(),
                "per_page" => $this->getProductsPerPage(),
                "amount_of_pages" => $this->getProductsCollection()->getLastPageNumber()
            ];
        }
        return false;
    }

    public function syncAPage($page, $storeId = null)
    {
        $transmitted = 0;
        $errors = 0;
        $this->initSync($storeId);
        $this->setCurrentPage($page);
        $products = $this->getProductsCollection();
        $dataBulk = $this->getModifiedProductsAsBulk($products);
        $amount = count($dataBulk);
        $data_json = [
            "bulk"       => $dataBulk,
            "properties" => [
                "current_page"       => $page,
                "total_pages"        => 1,
                "amount_of_products" => $amount,
                "extension_version"  => $this->getEnvironment()->getVersion(),
                "store"              => $this->getStoreId()
            ]
        ];
        $syncResponse = $this->getApiclient()->sendProducts($data_json);
        if ($syncResponse != "{}") {
            $errors ++;
            $this->logger->error("Can't send products", ['error' => (string)$syncResponse]);
        } else {
            $transmitted = $amount;
        }
        $this->response["data"] = ["total" => $amount, "transmitted" => $transmitted];

        $this->setEndTime(microtime(true));

        $sync_time = $this->getEndTime() - $this->getStartTime();

        $this->logger
            ->notice(
                "Synchronization finished, took $sync_time seconds, transmitted " .
                $transmitted . "/" . $amount . " products, " .
                $errors . " errors"
            );

        return ["errors" => $errors, "transmitted" => $transmitted, "count" => $amount, "pages" => 1];
    }

    /**
     * Function to build bulk of products with additional info
     *
     * @param $productCollection
     * @return array
     */
    public function getModifiedProductsAsBulk($productCollection)
    {
        $dataBulk = [];

        try {
            foreach ($productCollection as $product) {
                $version = $this->getEnvironment()->getVersion();

                $productId = $product->getId();

                $webNames = $this->generateWebsitesList($product->getWebsiteIds());
                $categoryNames = $this->generateCategoryList($product->getCategoryIds());
                $storeData = $this->generateStores($product);
                $stockItem = $this->stockItem->load($product->getId(), 'product_id');

                // Build product structure to send
                $newProduct = [
                    'item_id'    => $productId,
                    'name'       => $product->getName(),
                    'price'      => $product->getPrice(),
                    'currency'   => $this->environment->getCurrencyCode(),
                    'stores'     => $this->getStoreCodes(),
                    'properties' => array_merge(
                        $product->getData(),
                        [
                            'qty'                => $stockItem->getQty(),
                            'is_in_stock'        => $stockItem->getIsInStock(),
                            'state'              => $product->getStatus(),
                            'currency'           => $product->getCurrencyCode(),
                            'final_price'        => $product->getFinalPrice(),
                            'tax_amount'         => $product->getTaxAmount(),
                            'tax_rate'           => $product->getTaxRate(),
                            'prodcing'           => $product->getPrice(),
                            'imgUrl'             => $this->generateImgUrl($product),
                            'websites'           => $webNames,
                            'category_ids'       => $product->getCategoryIds(),
                            'category_names'     => $categoryNames,
                            'version'            => $version,
                            'upsell_products'    => $product->getUpSellProductIds(),
                            'crosssell_products' => $product->getCrossSellProductIds(),
                            'related_products'   => $product->getRelatedProductIds(),
                            'parent_id'          => $this->generateParentIDs($product),
                            'stores'             => $storeData,
                            'isSalable'          => $product->isSalable()
                        ]
                    )
                ];

                $dataBulk[] = $newProduct;
            }
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Model/Sync->getModifiedProductsAsBulk() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }

        return $dataBulk;
    }

    /**
     * Function to generate Array of parent products
     *
     * @param $product
     * @return array
     */
    private function generateParentIDs($product)
    {
        $parent_ids = [];

        try {
            $parent_ids = $product->getTypeInstance()->getParentIdsByChild($product->getId());
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Model/Sync->generateParentIDs() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }

        return $parent_ids;
    }

    /**
     * Function that generates Array with the info of all the store related to the product
     *
     * @param $product
     * @return array
     */
    private function generateStores($product)
    {
        $storeData = [];
        try {
            $storeIds = $product->getStoreIds();
            $storeCodes = [];
            // Check if product has storeIds()
            if (!empty($storeIds)) {
                $storeInfo = [];
                foreach ($storeIds as $storeId) {
                    $store = $this->getStoreManager()->getStore($storeId);
                    $storeCodes[] = $store->getCode();
                    $storeInfo["website"] = [$store->getWebsite()->getId() => $store->getWebsite()->getData()];
                    $storeInfo["name"] = $store->getName();
                    $storeInfo["id"] = $storeId;
                    $storeInfo["storeInUrl"] = $store->getStoreInUrl();
                    $storeInfo["group"] = [
                        $store->getGroup()->getId() => [
                            "name" => $store->getGroup()->getName(),
                            "id" => $store->getGroup()->getId(),
                            "data" => $store->getGroup()->getData()
                        ]
                    ];
                    $storeInfo['store_data'] = $store->getData();
                    $storeData[$storeId] = $storeInfo;
                }
                $this->setStoreCodes($storeCodes);
            }
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Model/Sync->generateStores() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }

        return $storeData;
    }

    /**
     * Function to generate simple imageUrl for the product
     *
     * @param $product
     * @return string
     */
    private function generateImgUrl($product)
    {
        $imgUrl = "";

        try {
            $this->galleryReadHandler->execute($product);

            $imgUrl = "";
            if ($product->getMediaGalleryImages()) {
                foreach ($product->getMediaGalleryImages() as $image) {
                    $imgUrl = $image->getUrl();
                    break;
                }
            }
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Model/Sync->generateImgUrl() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }
        return $imgUrl;
    }

    /**
     * Function to generate Array of product categories
     *
     * @param $categoryIds
     * @return array
     */
    private function generateCategoryList($categoryIds)
    {
        $categoryNames = [];

        try {
            // Check if product has categoryIds()
            if (!empty($categoryIds)) {
                foreach ($categoryIds as $catId) {
                    // check if key _$catId doesn't exist in $catIdName Array
                    if (!array_key_exists("_$catId", $this->getCatIdName())) {
                        $this->populateCatIdNameDict($categoryIds);
                        // populate dict for $catIdName
                    }
                    $catIdName = $this->getCatIdName();
                    $categoryNames[] = $catIdName["_$catId"];
                }
            }
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Model/Sync->generateCategoryList() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }
        return $categoryNames;
    }

    /**
     * Populate Category Dict
     *
     * @param $categoryIds - list of category ids
     * @return mixed - updated dictionary id-name
     */
    private function populateCatIdNameDict($categoryIds)
    {
        try {
            $categoryCollection = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect(['name', 'is_active'])
                ->addAttributeToFilter('entity_id', $categoryIds);

            foreach ($categoryCollection as $cat) {
                $innerCatId = $cat->getId();
                if (!array_key_exists("_$innerCatId", $this->getCatIdName())) {
                    $catName = $cat->getName();
                    $isActive = $cat->getIsActive();
                    if ($isActive) {
                        $this->setCatIdName("_$innerCatId", $catName);
                    } else {
                        $this->setCatIdName("_$innerCatId", "inactive_$catName");
                        $catIdName["_$innerCatId"] = "inactive_$catName";
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Model/Sync->populateIdNameDict() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }
        return $this->getCatIdName();
    }

    /**
     * Function to generate Array of product Websites
     *
     * @param $webIds
     * @return array
     */
    private function generateWebsitesList($webIds)
    {
        $webNames = [];

        try {
            // Check if product has webIds
            if (!empty($webIds)) {
                foreach ($webIds as $webId) {
                    // check if key _$webId doesn't exist in $webIdName Array
                    if (!array_key_exists("_$webId", $this->getWebIdName())) {
                        // populate dict for $webIdName
                        $this->populateWebsiteIdNameDict($webIds);
                    }
                    $webIdName = $this->getWebIdName();
                    $webNames[] = $webIdName["_$webId"];
                }
            }
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Model/Sync->generateCategoryList() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }

        return $webNames;
    }

    /**
     * Populate Website Name Dict
     *
     * @param $websiteIds
     * @return mixed
     */
    private function populateWebsiteIdNameDict($websiteIds)
    {
        try {
            foreach ($websiteIds as $websiteId) {
                if (!array_key_exists("_$websiteId", $this->getWebIdName())) {
                    $website = $this->getStoreManager()->getWebsite($websiteId);
                    if (isset($website)) {
                        $websiteName = $website->getName();
                        $this->setWebIdName("_$websiteId", $websiteName);
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger
                ->critical(
                    "Model/Sync->populateWebsiteIdNameDict() Exception: ",
                    [
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getCode()
                    ]
                );
        }
        return $this->getWebIdName();
    }

    /**
     * Return the syncing response
     *
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }
}
