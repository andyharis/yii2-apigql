<?php
/**
 * Created by IntelliJ IDEA.
 * User: User
 * Date: 17.01.2017
 * Time: 18:29
 */

namespace frontend\components;


use frontend\models\AccessRights;
use frontend\models\AccessRightsTemplates;
use frontend\models\Address;
use frontend\models\Albums;
use frontend\models\Artists;
use frontend\models\Building;
use frontend\models\Checklist;
use frontend\models\ClientAddresses;
use frontend\models\Clients;
use frontend\models\CodeTableItem;
use frontend\models\ClientsRooms;
use frontend\models\ClientsSites;
use frontend\models\ContactPersons;
use frontend\models\Customer;
use frontend\models\Delivery;
use frontend\models\DeliveryProduct;
use frontend\models\DeliveryProductSerialNo;
use frontend\models\EmailTemplates;
use frontend\models\Employee;
use frontend\models\Files;
use frontend\models\Filesdata;
use frontend\models\Filetags;
use frontend\models\Friends;
use frontend\models\Install;
use frontend\models\Item;
use frontend\models\ItemCategory;
use frontend\models\ItemSubCategory;
use frontend\models\Job;
use frontend\models\JobCategory;
use frontend\models\JobInvoice;
use frontend\models\JobItem;
use frontend\models\JobLabour;
use frontend\models\JobOrder;
use frontend\models\JobProduct;
use frontend\models\JobProductPurchaseOrderProduct;
use frontend\models\JobTask;
use frontend\models\JobTaskHistory;
use frontend\models\JobVisit;
use frontend\models\JobVisitEmployee;
use frontend\models\JobVisitLabour;
use frontend\models\JobVisitProduct;
use frontend\models\Labour;
use frontend\models\Markup;
use frontend\models\Members;
use frontend\models\OftenUsed;
use frontend\models\Opportunity;
use frontend\models\Product;
use frontend\models\ProductProduct;
use frontend\models\PurchaseOrder;
use frontend\models\PurchaseOrderProduct;
use frontend\models\Quote;
use frontend\models\QuoteCategory;
use frontend\models\QuoteCategoryLocations;
use frontend\models\QuoteCheckItem;
use frontend\models\QuoteItem;
use frontend\models\QuoteModuleSource;
use frontend\models\QuoteTemplate;
use frontend\models\QuoteTemplateCategory;
use frontend\models\QuoteTemplateItem;
use frontend\models\QuoteTemplateUpgrade;
use frontend\models\QuoteUpgrade;
use frontend\models\Role;
use frontend\models\SecUser;
use frontend\models\SecUserRole;
use frontend\models\Songs;
use frontend\models\StockCheck;
use frontend\models\StockCheckProducts;
use frontend\models\Suburb;
use frontend\models\Supplier;
use frontend\models\SupplierAddresses;
use frontend\models\SupplierProduct;
use yii\web\BadRequestHttpException;

class GraphQl
{

  public static function rec($data, $as = false)
  {
    $newNames = ['as' => 'zvb' . rand(0, 10) . rand(99, 9999999)];
    foreach ($data as $col => $val) {
      if ($col === 0 || $col === 1) {
        if (isset($newNames['as']))
          unset($newNames['as']);
        $newNames[$col] = preg_replace('/\w+\./', '', $val);
      } else {
        if (!is_array($val)) {
          $newNames[$col] = $val;
        } else {
          if ($col == 'conditions')
            $newNames[$col] = $val;
          else
            $newNames[$col] = self::rec($val, $newNames['as']);
        }
      }
    }
    return $newNames;
  }

  public static function createNames($class)
  {
    $names = self::names()[$class];
    return self::rec($names);
  }

  public static function names()
  {
    $template = function ($class, $r1 = false, $r2 = false, $has = 0, $generate = true, $conditions = false) {
      $result = [
        'className' => $class::className(),
        'tbl' => $class::tableName(),
        'pk' => $class::primaryKey()[0],
        'generate' => $generate,
        'conditions' => []
      ];
      if ($conditions)
        $result['conditions'] = $conditions;
      if ($r1) {
        $result['rel'] = [
          $r1,
          $r2,
          $has
        ];
      }
      return $result;
    };
    $names = [];
    // All
    $names['files'] = $template(Filesdata::className());
    $names['tags'] = $template(Filetags::className());
    $names['role'] = $template(Role::className());
    $names['role']['accessRights'] = $template(AccessRights::className(), 'iRoleID', 'iID', 1);
    $names['role']['accessRights']['rights'] = $template(AccessRightsTemplates::className(), 'iID', 'iAccessRightsID', 1);
    $names['members'] = $template(Members::className());
    $names['members']['roles'] = $template(SecUserRole::className(), 'iSecUserID', 'iID', 1);
    $names['members']['roles']['role'] = $template(Role::className(), 'iID', 'iRoleID');
    $names['members']['roles']['role']['accessRights'] = $template(AccessRights::className(), 'iRoleID', 'iID');
    $names['members']['roles']['role']['accessRights']['rights'] = $template(AccessRightsTemplates::className(), 'iID', 'iAccessRightsID', 1);
    $names['secUser'] = $template(Members::className());
    $names['roles'] = $template(SecUserRole::className());
    $names['roles']['role'] = $template(Role::className(), 'iID', 'iRoleID');
    $names['secUser']['roles'] = $template(SecUserRole::className(), 'iSecUserID', 'iID', 1);
    $names['secUser']['roles']['role'] = $template(Role::className(), 'iID', 'iRoleID');
    $names['secUser']['roles']['role']['accessRights'] = $template(AccessRights::className(), 'iRoleID', 'iID');
    $names['secUser']['roles']['role']['accessRights']['rights'] = $template(AccessRightsTemplates::className(), 'iID', 'iAccessRightsID', 1);
    $names['accessRights'] = $template(AccessRights::className());
    $names['accessRights']['rights'] = $template(AccessRightsTemplates::className(), 'iID', 'iAccessRightsID', 1);
    $names['rights'] = $template(AccessRightsTemplates::className());
    $names['codeTableItem'] = $template(CodeTableItem::className());
    $names['clientAddresses'] = $template(ClientAddresses::className());
    $names['itemCategory'] = $template(ItemCategory::className());
    $names['itemCategory']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iItemCategoryID', 'iID', 1);
    $names['itemSubCategory'] = $template(ItemSubCategory::className());
    $names['itemSubCategory']['category'] = $template(ItemCategory::className(), 'iID', 'iItemCategoryID');
    $names['itemSubCategory']['markup'] = $template(Markup::className(), 'iItemSubCategoryID', 'iID', 1);
    $names['product'] = $template(Product::className());
    $names['purchaseOrder'] = $template(PurchaseOrder::className());
    $names['supplierProduct'] = $template(SupplierProduct::className());
    $names['suburb'] = $template(Suburb::className());
    $names['employee'] = $template(Employee::className());
    $names['supplier'] = $template(Supplier::className());
    $names['productProduct'] = $template(ProductProduct::className());
    $names['opportunity'] = $template(Opportunity::className());
    $names['address'] = $template(Address::className());
    $names['quoteCategory'] = $template(QuoteCategory::className());
    $names['quoteCategory']['quoteUpgrade'] = $template(QuoteUpgrade::className(), 'iQuoteCategoryID', 'iID', 1);
    $names['quoteItem'] = $template(QuoteItem::className());
    $names['quoteItem']['item'] = $template(Item::className(), 'iID', 'iItemID');
    $names['quoteItem']['item']['markup'] = $template(Markup::className(), 'iItemSubCategoryID', 'iItemSubCategoryID', 1);
    $names['quoteItem']['item']['labour'] = $template(Labour::className(), 'iID', 'iID');
    $names['quoteItem']['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
    $names['quoteItem']['item']['supplierProduct'] = $template(SupplierProduct::className(), 'iProductID', 'iID');
    $names['quoteCheckItem'] = $template(QuoteCheckItem::className());
    $names['contactPersons'] = $template(ContactPersons::className());
    $names['contactPersons']['address'] = $template(ClientAddresses::className(), 'iContactPersonsID', 'iID');
    $names['contactPersons']['address']['suburb'] = $template(Suburb::className(), 'iID', 'iSuburbID');
    $names['contactPersons']['client'] = $template(Clients::className(), 'iID', 'iClientID');
    $names['clientsRooms'] = $template(ClientsRooms::className());
    $names['clientsRooms']['building'] = $template(Building::className(), 'iID', 'iBuildingID');
    $names['clientsRooms']['sites'] = $template(ClientsSites::className(), 'iID', 'iClientSitesID');
    $names['clientsSites'] = $template(ClientsSites::className());
    $names['clientsSites']['buildings'] = $template(Building::className(), 'iClientSitesID', 'iID', 1);
    $names['clientsSites']['buildings']['rooms'] = $template(ClientsRooms::className(), 'iBuildingID', 'iID', 1);
    $names['buildings'] = $template(Building::className());
    $names['emailTemplates'] = $template(EmailTemplates::className());
    // Clients
    $names['clients'] = $template(Clients::className());
    $names['clients']['quote'] = $template(Quote::className(), 'iClientID', 'iID', 1);
    $names['clients']['contactPersons'] = $template(ContactPersons::className(), 'iClientID', 'iID', 1);
    $names['clients']['clientsRooms'] = $template(ClientsRooms::className(), 'iClientID', 'iID', 1);
    $names['clients']['clientsSites'] = $template(ClientsSites::className(), 'iClientID', 'iID', 1);
    $names['clients']['clientsSites']['buildings'] = $template(Building::className(), 'iClientSitesID', 'iID', 1);
    $names['clients']['buildings'] = $template(Building::className(), 'iClientID', 'iID', 1);
    $names['clients']['clientsSites']['buildings']['clientsRooms'] = $template(ClientsRooms::className(), 'iBuildingID', 'iID', 1);

//    $names['clients']['clientsSites']['clientsRooms'] = $template(ClientsRooms::className(), 'iClientSitesID', 'iID', 1);
    $names['clientsSites']['clientsRooms'] = $template(ClientsRooms::className(), 'iClientSitesID', 'iID', 1);
    $names['clientsSites']['buildings'] = $template(Building::className(), 'iClientSitesID', 'iID', 1);
    $names['clientsSites']['buildings']['clientsRooms'] = $template(ClientsRooms::className(), 'iBuildingID', 'iID', 1);
    //job
    $names['job'] = $template(Job::className());
//    $names['accessRights'] = $template(AccessRights::className());
//    $names['accessRights']['role'] = $template(Role::className(), 'iID', 'iRoleID');
    $names['jobOrder'] = $template(JobOrder::className());
    $names['jobInvoice'] = $template(JobInvoice::className());
    $names['jobTask'] = $template(JobTask::className());
    $names['jobTaskHistory'] = $template(JobTaskHistory::className());
    $names['jobVisit'] = $template(JobVisit::className());
    $names['jobVisit']['jobVisitEmployee'] = $template(JobVisitEmployee::className(), 'iJobVisitID', 'iID', 1);
    $names['jobVisit']['jobVisitEmployee']['jobVisitLabour'] = $template(JobVisitLabour::className(), 'iJobVisitEmployeeID', 'iID', 1);
    $names['jobVisit']['jobVisitProduct'] = $template(JobVisitProduct::className(), 'iJobVisitID', 'iID', 1);
    $names['jobItem'] = $template(JobItem::className());
    $names['jl'] = $template(JobItem::className());
    $names['jobVisitLabour'] = $template(JobVisitLabour::className());
    $names['jobVisitProduct'] = $template(JobVisitProduct::className());
    $names['jobVisitEmployee'] = $template(JobVisitEmployee::className());
    $names['jobLabour'] = $template(JobLabour::className());
    $names['labour'] = $template(Labour::className());
    $names['jobProduct'] = $template(JobProduct::className());
    $names['jobProductPurchaseOrderProduct'] = $template(JobProductPurchaseOrderProduct::className());
    $names['stockCheckProducts'] = $template(StockCheckProducts::className());
    $names['stockCheckProducts']['item'] = $template(Item::className(), 'iID', 'id_product');
    $names['stockCheck'] = $template(StockCheck::className());
    $names['stockCheck']['purchaseOrders'] = $template(PurchaseOrder::className(), 'id_stock_check', 'iID', 1);
    $names['stockCheck']['stockCheckProducts'] = $template(StockCheckProducts::className(), 'id_stock_check', 'iID', 1);
    $names['stockCheck']['stockCheckProducts']['item'] = $template(Item::className(), 'iID', 'id_product');


    $names['job']['jobCategory'] = $template(JobCategory::className(), 'iJobID', 'iID', 1);
    $names['job']['jobCategory']['room'] = $template(ClientsRooms::className(), 'iID', 'iClientRoomsID');
    $names['job']['jobCategory']['install'] = $template(Install::className(), 'iID', 'iInstallID');
    $names['job']['jobCategory']['jl'] = $template(JobItem::className(), 'iJobCategoryID', 'iID', 1);
    $names['job']['jobCategory']['jl']['jobLabour'] = $template(JobLabour::className(), 'iID', 'iID', 1);
    $names['job']['jobCategory']['jl']['jobLabour']['labour'] = $template(Labour::className(), 'iID', 'iLabourID');
    $names['job']['jobCategory']['jl']['jobLabour']['labour']['item'] = $template(Item::className(), 'iID', 'iID');
    $names['job']['jobCategory']['jl']['jobLabour']['jobVisitLabours'] = $template(JobVisitLabour::className(), 'iJobLabourID', 'iID', 1);
    $names['job']['jobCategory']['jobItem'] = $template(JobItem::className(), 'iJobCategoryID', 'iID', 1);
    $names['job']['jobCategory']['jobItem']['jobLabour'] = $template(JobLabour::className(), 'iID', 'iID', 1);
    $names['job']['jobCategory']['jobItem']['jobProduct'] = $template(JobProduct::className(), 'iID', 'iID', 0);
    $names['job']['jobCategory']['jobItem']['jobLabour']['iitem'] = $template(Item::className(), 'iID', 'iLabourID', 0);
    $names['job']['jobCategory']['jobItem']['jobProduct']['iitem'] = $template(Item::className(), 'iID', 'iProductID', 0);
    $names['job']['jobCategory']['jobItem']['jobProduct']['jobVisitProducts'] = $template(JobVisitProduct::className(), 'iJobProductID', 'iID', 1);
    $names['job']['jobCategory']['jobItem']['jobProduct']['product'] = $template(Product::className(), 'iID', 'iProductID');
    $names['job']['jobCategory']['jobItem']['jobProduct']['product']['item'] = $template(Item::className(), 'iID', 'iID');
    $names['job']['quote'] = $template(Quote::className(), 'iID', 'iQuoteID', 1);
    $names['job']['quote']['supportEmployee'] = $template(Employee::className(), 'iID', 'iSupportEmployeeID');
    $names['job']['jobOrder'] = $template(JobOrder::className(), 'iJobID', 'iID', 1);
    $names['job']['jobInvoice'] = $template(JobInvoice::className(), 'iJobID', 'iID', 1);
    $names['job']['jobTask'] = $template(JobTask::className(), 'iJobID', 'iID', 1);
    $names['job']['jobItem'] = $template(JobItem::className(), 'iJobID', 'iID', 1);
    $names['job']['purchaseOrder'] = $template(PurchaseOrder::className(), 'iJobID', 'iID', 1);
    $names['job']['purchaseOrder']['supplier'] = $template(Supplier::className(), 'iID', 'iSupplierID');
    $names['job']['purchaseOrder']['employee'] = $template(Employee::className(), 'iID', 'iEmployeeID');
//    $names['job']['jl'] = $template(JobItem::className(), 'iJobID', 'iID', 1);
//    $names['job']['jl']['jobCategory'] = $template(JobCategory::className(), 'iID', 'iJobCategoryID');
//    $names['job']['jl']['jobLabour'] = $template(JobLabour::className(), 'iID', 'iID', 1);
//    $names['job']['jl']['jobLabour']['jobVisitLabours'] = $template(JobVisitLabour::className(), 'iJobLabourID', 'iID', 1);
//    $names['job']['jl']['jobLabour']['jobVisitLabour'] = $template(JobVisitLabour::className(), 'iJobLabourID', 'iID', 1);
//    $names['job']['jl']['jobLabour']['labour'] = $template(Labour::className(), 'iID', 'iLabourID');
//    $names['job']['jl']['jobLabour']['labour']['item'] = $template(Item::className(), 'iID', 'iID');
//    $names['job']['jl']['jobLabour']['labour']['item']['itemCategory'] = $template(ItemCategory::className(), 'iID', 'iItemCategoryID');
    $names['job']['jobItem']['jobProduct'] = $template(JobProduct::className(), 'iID', 'iID', 0);
//    $names['job']['jobItem']['jobProduct']['jobVisitProducts'] = $template(JobVisitProduct::className(), 'iJobProductID', 'iID');
//    $names['job']['jobItem']['jobProduct']['product'] = $template(Product::className(), 'iID', 'iProductID');
//    $names['job']['jobItem']['jobProduct']['product']['item'] = $template(JobProduct::className(), 'iID', 'iID');
    $names['job']['jobVisit'] = $template(JobVisit::className(), 'iJobID', 'iID', 1);
//    $names['job']['jobTask']['jobTaskHistory'] = $template(JobTaskHistory::className(), 'iID', 'iJobTaskID');
    $names['job']['jobItem']['jobProduct']['jobProductPurchaseOrderProduct'] = $template(JobProductPurchaseOrderProduct::className(), 'iJobProductID', 'iID');
    $names['job']['jobItem']['jobProduct']['jobVisitProduct'] = $template(JobVisitProduct::className(), 'iJobProductID', 'iID');
//    $names['job']['jobItem']['jobCategory'] = $template(JobCategory::className(), 'iID', 'iJobCategoryID');
//    $names['job']['jobItem']['jobCategory']['install'] = $template(Install::className(), 'iID', 'iInstallID');
    $names['job']['jobVisit']['jobVisitProduct'] = $template(JobVisitProduct::className(), 'iJobVisitID', 'iID');
    $names['job']['jobVisit']['jobVisitProduct']['jobProduct'] = $template(JobProduct::className(), 'iID', 'iJobProductID');
    $names['job']['jobVisit']['jobVisitProduct']['jobProduct']['product'] = $template(Product::className(), 'iID', 'iProductID');
    $names['job']['jobVisit']['jobVisitProduct']['jobProduct']['product']['item'] = $template(Item::className(), 'iID', 'iID');
    $names['job']['jobVisit']['jobVisitEmployee'] = $template(JobVisitEmployee::className(), 'iJobVisitID', 'iID');
    $names['job']['jobVisit']['jobVisitEmployee']['employee'] = $template(Employee::className(), 'iID', 'iEmployeeID');
    $names['job']['jobVisit']['jobVisitEmployee']['jobVisitLabour'] = $template(JobVisitLabour::className(), 'iJobVisitEmployeeID', 'iID');
    $names['job']['jobVisit']['jobVisitEmployee']['jobVisitLabour']['jobLabour'] = $template(JobLabour::className(), 'iID', 'iJobLabourID');
    $names['job']['jobVisit']['jobVisitEmployee']['jobVisitLabour']['jobLabour']['labour'] = $template(Labour::className(), 'iID', 'iLabourID');
    // Job category
    $names['jobCategory'] = $template(JobCategory::className());
    $names['jobCategory']['clientsSites'] = $template(ClientsSites::className(), 'iID', 'iClientSitesID');
    $names['jobCategory']['clientsRooms'] = $template(ClientsRooms::className(), 'iID', 'iClientRoomsID');
    // Quote Upgrade
    $names['quoteUpgrade'] = $template(QuoteUpgrade::className());
    $names['quoteUpgrade']['quoteItem'] = $template(QuoteItem::className(), 'iQuoteUpgradeID', 'iID', 1);
    $names['quoteUpgrade']['quoteItem']['item'] = $template(Item::className(), 'iID', 'iItemID');
    // Quote Template Category
    $names['quoteTemplateCategory'] = $template(QuoteTemplateCategory::className());
    $names['quoteTemplateCategory']['quoteTemplateUpgrade'] = $template(QuoteTemplateUpgrade::className(), 'iQuoteTemplateCategoryID', 'iID', 1);
    // Quote Template Upgrade

    // Quote
    $names['quote'] = $template(Quote::className());
    $names['quote']['job'] = $template(Job::className(),'iQuoteID','iID');
    $names['quote']['clients'] = $template(Clients::className(), 'iID', 'iClientID');
    $names['quote']['employee'] = $template(Employee::className(), 'iID', 'iEmployeeID');
//    $names['quote']['customer'] = $template(Customer::className(), 'iID', 'iCustomerID');
    $names['quote']['contactPersons'] = $template(ContactPersons::className(), 'iID', 'iContactPersonsID');
//    $names['quote']['quoteItem'] = $template(QuoteItem::className(), 'iQuoteID', 'iID', 1);
//    $names['quote']['quoteItem']['item'] = $template(Item::className(), 'iID', 'iItemID');
    $names['quote']['quoteCategory'] = $template(QuoteCategory::className(), 'iQuoteID', 'iID', 1);
    $names['quote']['quoteCategory']['checklist'] = $template(Checklist::className(), 'entityId', 'iID', 0, false, [
      'or', ['entityName' => Checklist::tableName()], ['entityName' => null]
    ]);
    $names['quote']['quoteCategory']['quoteCategoryLocations'] = $template(QuoteCategoryLocations::className(), 'iQuoteCategoryID', 'iID', 1);
//    $names['quote']['quoteCategory']['quoteItem'] = $template(QuoteItem::className(), 'iQuoteCategoryID', 'iID', 1);
//    $names['quote']['quoteCategory']['quoteItem']['item'] = $template(Item::className(), 'iID', 'iItemID');
    $names['quote']['quoteCategory']['quoteUpgrade'] = $template(QuoteUpgrade::className(), 'iQuoteCategoryID', 'iID', 1);
    $names['quote']['quoteCategory']['quoteUpgrade']['moduleSource'] = $template(QuoteModuleSource::className(), 'iQuoteUpgradeID', 'iID', 1);
    $names['quote']['quoteCategory']['quoteUpgrade']['quoteItem'] = $template(QuoteItem::className(), 'iQuoteUpgradeID', 'iID', 1);
    $names['quote']['quoteCategory']['quoteUpgrade']['quoteItem']['item'] = $template(Item::className(), 'iID', 'iItemID');
    $names['quote']['quoteCategory']['quoteUpgrade']['quoteItem']['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
    // Template Quote
    $names['quoteTemplate'] = $template(QuoteTemplate::className());
//    $names['quoteTemplate']
    $names['quoteTemplate']['files'] = $template(Filesdata::className(), 'entityId', 'iID', 0, false, [
      'or', ['entityName' => Files::tableName()], ['entityName' => null]
    ]);
    $names['quoteTemplateItem'] = $template(QuoteTemplateItem::className());
    $names['quoteTemplateItem']['item'] = $template(Item::className(), 'iID', 'iItemID');
    $names['quoteTemplateItem']['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
    $names['quoteTemplateItem']['item']['supplierProduct'] = $template(SupplierProduct::className(), 'iProductID', 'iID');
    $names['quoteTemplateItem']['item']['labour'] = $template(Labour::className(), 'iID', 'iID');
    $names['quoteTemplateItem']['item']['markup'] = $template(Markup::className(), 'iItemSubCategoryID', 'iItemSubCategoryID', 1);
    $names['quoteTemplate']['quoteTemplateItem'] = $template(QuoteTemplateItem::className(), 'iQuoteTemplateID', 'iID', 1);
    $names['quoteTemplate']['quoteTemplateItem']['item'] = $template(Item::className(), 'iID', 'iItemID');
    $names['quoteTemplate']['quoteTemplateCategory'] = $template(QuoteTemplateCategory::className(), 'iQuoteTemplateID', 'iID', 1);
    $names['quoteTemplate']['quoteTemplateCategory']['quoteTemplateUpgrade'] = $template(QuoteTemplateUpgrade::className(), 'iQuoteTemplateCategoryID', 'iID', 1);
    $names['quoteTemplate']['quoteTemplateCategory']['quoteTemplateUpgrade']['checklist'] = $template(Checklist::className(), 'entityId', 'iID', 0, false, [
      'or', ['entityName' => Checklist::tableName()], ['entityName' => null]
    ]);
    $names['quoteTemplate']['quoteTemplateCategory']['quoteTemplateUpgrade']['quoteTemplateItem'] = $template(QuoteTemplateItem::className(), 'iQuoteTemplateUpgradeID', 'iID', 1);
    $names['quoteTemplate']['quoteTemplateCategory']['quoteTemplateUpgrade']['quoteTemplateItem']['item'] = $template(Item::className(), 'iID', 'iItemID');
    $names['quoteTemplate']['quoteTemplateCategory']['quoteTemplateUpgrade']['quoteTemplateItem']['item']['supplierProduct'] = $template(SupplierProduct::className(), 'iProductID', 'iID');
    $names['quoteTemplate']['quoteTemplateCategory']['quoteTemplateUpgrade']['quoteTemplateItem']['item']['quoteItem'] = $template(QuoteItem::className(), 'iItemID', 'iID');
    $names['quoteTemplate']['quoteTemplateCategory']['quoteTemplateUpgrade']['quoteTemplateItem']['item']['quoteItem']['item'] = $template(Item::className(), 'iID', 'iItemID');
    // Template upgrade
    $names['quoteTemplateUpgrade'] = $template(QuoteTemplateUpgrade::className());
    $names['quoteTemplateCategory'] = $template(QuoteTemplateCategory::className());
    $names['quoteTemplateCategory']['quoteTemplate'] = $template(QuoteTemplate::className(), 'iID', 'iQuoteTemplateID');
    $names['quoteTemplateUpgrade']['quoteTemplateCategory'] = $template(QuoteTemplateCategory::className(), 'iID', 'iQuoteTemplateCategoryID');
    $names['quoteTemplateUpgrade']['quoteTemplateItem'] = $template(QuoteTemplateItem::className(), 'iQuoteTemplateUpgradeID', 'iID', 1);
    $names['quoteTemplateUpgrade']['quoteTemplateItem']['item'] = $template(Item::className(), 'iID', 'iItemID');
    $names['quoteTemplateUpgrade']['quoteTemplateItem']['item']['quoteItem'] = $template(QuoteItem::className(), 'iItemID', 'iID');
    $names['quoteTemplateUpgrade']['quoteTemplateItem']['item']['quoteItem']['item'] = $template(Item::className(), 'iID', 'iItemID');

    // Markup
    $names['markup'] = $template(Markup::className());
    $names['markup']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
    // Delivery
    $names['delivery'] = $template(Delivery::className());
    $names['delivery']['purchaseOrder'] = $template(PurchaseOrder::className(), 'iID', 'iPurchaseOrderID');
    $names['delivery']['supplier'] = $template(Supplier::className(), 'iID', 'iSupplierID');
    $names['delivery']['deliveryProduct'] = $template(DeliveryProduct::className(), 'iDeliveryID', 'iID', 1);
    $names['delivery']['deliveryProduct']['product'] = $template(Item::className(), 'iID', 'iProductID');
    $names['delivery']['deliveryProduct']['product']['item'] = $template(Item::className(), 'iID', 'iID');
    $names['delivery']['deliveryProduct']['product']['item']['itemCategory'] = $template(ItemCategory::className(), 'iID', 'iItemCategoryID');
    $names['delivery']['deliveryProduct']['product']['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
    $names['delivery']['deliveryProduct']['supplierProduct'] = $template(SupplierProduct::className(), 'iID', 'iSupplierProductID');
    $names['delivery']['deliveryProduct']['deliveryProductSerialNo'] = $template(DeliveryProductSerialNo::className(), 'iDeliveryProductID', 'iID', 1);
    $names['delivery']['deliveryProduct']['purchaseOrderProduct'] = $template(PurchaseOrderProduct::className(), 'iID', 'iPurchaseOrderProductID');
    $names['delivery']['deliveryProduct']['purchaseOrderProduct']['supplierProduct'] = $template(SupplierProduct::className(), 'iID', 'iSupplierProductID');
    $names['delivery']['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['product'] = $template(Item::className(), 'iID', 'iProductID');
    $names['delivery']['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['product']['item'] = $template(Item::className(), 'iID', 'iID');
    $names['delivery']['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['product']['item']['itemCategory'] = $template(ItemCategory::className(), 'iID', 'iItemCategoryID');
    $names['delivery']['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['product']['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
    // Delivery product
    $names['deliveryProduct'] = $template(DeliveryProduct::className());
    $names['deliveryProduct']['purchaseOrderProduct'] = $template(PurchaseOrderProduct::className(), 'iID', 'iPurchaseOrderProductID');
    $names['deliveryProduct']['purchaseOrderProduct']['supplier'] = $template(Supplier::className(), 'iID', 'iSupplierID');
    $names['deliveryProduct']['purchaseOrderProduct']['supplierProduct'] = $template(SupplierProduct::className(), 'iID', 'iSupplierProductID');
    $names['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['product'] = $template(Item::className(), 'iID', 'iProductID');
    $names['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['product']['item'] = $template(Item::className(), 'iID', 'iID');
    $names['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['product']['item']['itemCategory'] = $template(ItemCategory::className(), 'iID', 'iItemCategoryID');
    $names['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['product']['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
    $names['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['item'] = $template(Item::className(), 'iID', 'iID');
    $names['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['item']['itemCategory'] = $template(ItemCategory::className(), 'iID', 'iItemCategoryID');
    $names['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
    $names['deliveryProduct']['deliveryProductSerialNo'] = $template(DeliveryProductSerialNo::className(), 'iDeliveryProductID', 'iID', 1);
    // Purchase order product
    $names['purchaseOrderProduct'] = $template(PurchaseOrderProduct::className());
    $names['purchaseOrderProduct']['deliveryProduct'] = $template(DeliveryProduct::className(), 'iDeliveryID', 'iID', 1);
    $names['purchaseOrderProduct']['supplierProduct'] = $template(SupplierProduct::className(), 'iID', 'iSupplierProductID');
    $names['purchaseOrderProduct']['supplierProduct']['product'] = $template(Product::className(), 'iID', 'iProductID');
    $names['purchaseOrderProduct']['supplierProduct']['product']['item'] = $template(Item::className(), 'iID', 'iID');
    $names['purchaseOrderProduct']['supplierProduct']['product']['item']['itemCategory'] = $template(ItemCategory::className(), 'iID', 'iItemCategoryID');
    $names['purchaseOrderProduct']['supplierProduct']['product']['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
    // Purchase orders
    $names['purchaseOrder']['supplier'] = $template(Supplier::className(), 'iID', 'iSupplierID');
    $names['purchaseOrder']['supplier']['address'] = $template(SupplierAddresses::className(), 'iSupplierID', 'iID');
    $names['purchaseOrder']['supplier']['address']['suburb'] = $template(Suburb::className(), 'iID', 'iSuburbID');
    $names['purchaseOrder']['job'] = $template(Job::className(), 'iID', 'iJobID');
    $names['purchaseOrder']['delivery'] = $template(Delivery::className(), 'iPurchaseOrderID', 'iID', 1);
    $names['purchaseOrder']['delivery']['purchaseOrder'] = $template(PurchaseOrder::className(), 'iID', 'iPurchaseOrderID');
    $names['purchaseOrder']['delivery']['supplier'] = $template(Supplier::className(), 'iID', 'iSupplierID');
    $names['purchaseOrder']['delivery']['deliveryProduct'] = $template(DeliveryProduct::className(), 'iDeliveryID', 'iID', 1);
//    $names['purchaseOrder']['delivery']['deliveryProduct']['purchaseOrderProduct'] = $template(PurchaseOrderProduct::className(), 'iID', 'iPurchaseOrderProductID');
//    $names['purchaseOrder']['delivery']['deliveryProduct']['purchaseOrderProduct']['supplierProduct'] = $template(SupplierProduct::className(), 'iID', 'iSupplierProductID');
//    $names['purchaseOrder']['delivery']['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['product'] = $template(Item::className(), 'iID', 'iProductID');
//    $names['purchaseOrder']['delivery']['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['product']['item'] = $template(Item::className(), 'iID', 'iID');
//    $names['purchaseOrder']['delivery']['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['product']['item']['itemCategory'] = $template(ItemCategory::className(), 'iID', 'iItemCategoryID');
//    $names['purchaseOrder']['delivery']['deliveryProduct']['purchaseOrderProduct']['supplierProduct']['product']['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
//    $names['deliveryProduct']['deliveryProductSerialNo'] = $template(DeliveryProductSerialNo::className(), 'iDeliveryProductID', 'iID', 1);
    $names['purchaseOrder']['delivery']['deliveryProduct']['deliveryProductSerialNo'] = $template(DeliveryProductSerialNo::className(), 'iDeliveryProductID', 'iID', 1);
    $names['purchaseOrder']['employee'] = $template(Employee::className(), 'iID', 'iEmployeeID');
    $names['purchaseOrder']['purchaseOrderProduct'] = $template(PurchaseOrderProduct::className(), 'iPurchaseOrderID', 'iID', 1);
    $names['purchaseOrder']['purchaseOrderProduct']['supplierProduct'] = $template(SupplierProduct::className(), 'iID', 'iSupplierProductID');
    $names['purchaseOrder']['purchaseOrderProduct']['supplierProduct']['product'] = $template(Product::className(), 'iID', 'iProductID');
    $names['purchaseOrder']['purchaseOrderProduct']['supplierProduct']['product']['item'] = $template(Item::className(), 'iID', 'iID');
    $names['purchaseOrder']['purchaseOrderProduct']['supplierProduct']['product']['item']['itemCategory'] = $template(ItemCategory::className(), 'iID', 'iItemCategoryID');
    $names['purchaseOrder']['purchaseOrderProduct']['supplierProduct']['product']['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');

    $names['product']['supplier'] = $template(Supplier::className(), 'iID', 'iSupplierID');
    $names['product']['item'] = $template(Item::className(), 'iID', 'iID');
    $names['product']['item']['itemCategory'] = $template(ItemCategory::className(), 'iID', 'iItemCategoryID');
    $names['product']['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
    $names['product']['stockCheckProducts'] = $template(StockCheckProducts::className(), 'id_product', 'iID');
    $names['productProduct']['item'] = $template(Item::className(), 'iID', 'iAltProductID');
    $names['productProduct']['item']['quoteItem'] = $template(QuoteItem::className(), 'iItemID', 'iID');
    $names['productProduct']['item']['quoteItem']['item'] = $template(Item::className(), 'iID', 'iItemID');

    $names['address']['suburb'] = $template(Suburb::className(), 'iID', 'iSuburbID');
    // Opportunity
    $names['opportunity']['createdBy'] = $template(Employee::className(), 'iID', 'iCreatorEmployeeID');
    $names['opportunity']['assignedBy'] = $template(Employee::className(), 'iID', 'iAssignedEmployeeID');
    $names['opportunity']['customer'] = $template(Customer::className(), 'iID', 'iCustomerID');
    $names['opportunity']['clients'] = $template(Clients::className(), 'iID', 'iClientID');
    $names['opportunity']['contactPersons'] = $template(ContactPersons::className(), 'iID', 'iContactPersonsID');

    $names['supplierProduct']['supplier'] = $template(Supplier::className(), 'iID', 'iSupplierID');
    $names['supplierProduct']['product'] = $template(Item::className(), 'iID', 'iProductID');
    $names['supplierProduct']['product']['item'] = $template(Item::className(), 'iID', 'iID');
    $names['supplierProduct']['product']['item']['itemCategory'] = $template(ItemCategory::className(), 'iID', 'iItemCategoryID');
    $names['supplierProduct']['product']['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
    // Supplier
    $names['supplier']['address'] = $template(SupplierAddresses::className(), 'iSupplierID', 'iID');
    $names['supplier']['address']['suburb'] = $template(Suburb::className(), 'iID', 'iSuburbID');
    $names['supplier']['supplierProduct'] = $template(SupplierProduct::className(), 'iSupplierID', 'iID', 1);
    $names['supplier']['purchaseOrders'] = $template(PurchaseOrder::className(), 'iSupplierID', 'iID', 1);
    $names['supplier']['purchaseOrders']['supplier'] = $template(Supplier::className(), 'iID', 'iSupplierID');
    $names['supplier']['purchaseOrders']['delivery'] = $template(Delivery::className(), 'iPurchaseOrderID', 'iID', 1);
    $names['supplier']['purchaseOrders']['delivery']['deliveryProduct'] = $template(DeliveryProduct::className(), 'iDeliveryID', 'iID', 1);
    $names['supplier']['purchaseOrders']['delivery']['deliveryProduct']['deliveryProductSerialNo'] = $template(DeliveryProductSerialNo::className(), 'iDeliveryProductID', 'iID', 1);
    $names['supplier']['purchaseOrders']['employee'] = $template(Employee::className(), 'iID', 'iEmployeeID');
    $names['supplier']['purchaseOrders']['purchaseOrderProduct'] = $template(PurchaseOrderProduct::className(), 'iPurchaseOrderID', 'iID', 1);
    $names['supplier']['purchaseOrders']['purchaseOrderProduct']['supplierProduct'] = $template(SupplierProduct::className(), 'iID', 'iSupplierProductID');
    // Install
    $names['install'] = $template(Install::className());
    $names['install']['customer'] = $template(Customer::className(), 'iID', 'iCustomerID');
    $names['install']['jobCategory'] = $template(JobCategory::className(), 'iInstallID', 'iID', 1);
    $names['install']['jobCategory']['clientsSites'] = $template(ClientsSites::className(), 'iID', 'iClientSitesID');
    $names['install']['jobCategory']['clientsRooms'] = $template(ClientsRooms::className(), 'iID', 'iClientRoomsID');
    $names['install']['clients'] = $template(Clients::className(), 'iID', 'iClientID');
    $names['install']['clientsSites'] = $template(ClientsSites::className(), 'iID', 'iClientSitesID');
    $names['install']['clientsSites']['clientsRooms'] = $template(ClientsRooms::className(), 'iClientSitesID', 'iID', 1);
    $names['install']['clientsSites']['clients'] = $template(Clients::className(), 'iID', 'iClientID');
    $names['install']['clientsRooms'] = $template(ClientsRooms::className(), 'iID', 'iClientRoomsID', 1);
    $names['install']['clientsRooms']['clients'] = $template(Clients::className(), 'iID', 'iClientID');
    $names['install']['contactPersons'] = $template(ContactPersons::className(), 'iID', 'iClientID');
    // Customer
    $names['customer'] = $template(Customer::className());
    $names['customer']['address'] = $template(Address::className(), 'iID', 'iAddressID');
    $names['customer']['address']['suburb'] = $template(Suburb::className(), 'iID', 'iSuburbID');
    // Item
    $names['item'] = $template(Item::className());
    $names['item']['used'] = $template(OftenUsed::className(), 'entityId', 'iID', 0, false, [
      'or', ['entityName' => OftenUsed::tableName()], ['entityName' => null]
    ]);
    $names['item']['checklist'] = $template(Checklist::className(), 'entityId', 'iID', 0, false, [
      'or', ['entityName' => Checklist::tableName()], ['entityName' => null]
    ]);
    $names['item']['files'] = $template(Filesdata::className(), 'entityId', 'iID', 0, false, [
      'or', ['entityName' => Filesdata::tableName()], ['entityName' => null]
    ]);
    $names['item']['files']['tags'] = $template(Filetags::className(), 'iFilesDataID', 'iID');
    $names['item']['quoteItem'] = $template(QuoteItem::className(), 'iItemID', 'iID');
    $names['item']['quoteItem']['item'] = $template(Item::className(), 'iID', 'iItemID');
    $names['item']['itemCategory'] = $template(ItemCategory::className(), 'iID', 'iItemCategoryID');
    $names['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
    $names['item']['itemSubCategory']['markup'] = $template(Markup::className(), 'iItemSubCategoryID', 'iID', 1);
    $names['item']['supplierProduct'] = $template(SupplierProduct::className(), 'iProductID', 'iID');
    $names['item']['supplierProducts'] = $template(SupplierProduct::className(), 'iProductID', 'iID', 1);
    $names['item']['supplierProducts']['supplier'] = $template(Supplier::className(), 'iID', 'iSupplierID');
    $names['item']['product'] = $template(Product::className(), 'iID', 'iID');
//    $names['item']['product']['item'] = $template(Item::className(), 'iID', 'iID');
//    $names['item']['product']['item']['itemCategory'] = $template(ItemCategory::className(), 'iID', 'iItemCategoryID');
//    $names['item']['product']['item']['itemSubCategory'] = $template(ItemSubCategory::className(), 'iID', 'iItemSubCategoryID');
//    $names['item']['product']['productProduct'] = $template(ProductProduct::className(), 'iProductID', 'iID');
    $names['item']['product']['supplier'] = $template(Supplier::className(), 'iID', 'iSupplierID');
//    $names['item']['product']['productProduct']['item'] = $template(Item::className(), 'iID', 'iProductID');
//    $names['item']['productProduct'] = $template(ProductProduct::className(), 'iProductID', 'iID', 1);
//    $names['item']['productProduct']['item'] = $template(Item::className(), 'iID', 'iAltProductID');
    $names['item']['markup'] = $template(Markup::className(), 'iItemSubCategoryID', 'iItemSubCategoryID', 1);
    $names['item']['labour'] = $template(Labour::className(), 'iID', 'iID');

    // Often used
    $names['used'] = $template(OftenUsed::className());
    $names['clientsRooms']['clientsSites'] = $template(ClientsSites::className(), 'iID', 'iClientSitesID');

    // Checklist
    $names['checklist'] = $template(Checklist::className());
    $names['files'] = $template(Filesdata::className());
    $names['labour'] = $template(Labour::className());
    $names['labour']['item'] = $template(Item::className(), 'iID', 'iID');
    $names['quoteTemplateUpgrade'] = $template(QuoteTemplateUpgrade::className());
    $names['quoteTemplateUpgrade']['checklist'] = $template(Checklist::className(), 'entityId', 'iID', 0, false, [
      'or', ['entityName' => Checklist::tableName()], ['entityName' => null]
    ]);
    $names['quoteTemplateUpgrade']['createdBy'] = $template(Employee::className(), 'iID', 'created_by');
    $names['quoteTemplateUpgrade']['editedBy'] = $template(Employee::className(), 'iID', 'edited_by');
    $names['quoteTemplateUpgrade']['quoteTemplateItem'] = $template(QuoteTemplateItem::className(), 'iQuoteTemplateUpgradeID', 'iID', 1);
    $names['quoteTemplateUpgrade']['quoteTemplateItem']['item'] = $template(Item::className(), 'iID', 'iItemID');
    $names['quoteTemplateUpgrade']['quoteTemplateItem']['item']['labour'] = $template(Labour::className(), 'iID', 'iID');
    $names['quoteTemplateUpgrade']['quoteTemplateItem']['item']['supplierProduct'] = $template(SupplierProduct::className(), 'iProductID', 'iID');
    $names['quoteTemplateUpgrade']['quoteTemplateItem']['item']['markup'] = $template(Markup::className(), 'iItemSubCategoryID', 'iItemSubCategoryID', 1);
    $names['quoteTemplateUpgrade']['quoteTemplateCategory'] = $template(QuoteTemplateCategory::className(), 'iID', 'iQuoteTemplateCategoryID');
    $names['quoteTemplateUpgrade']['quoteTemplateCategory']['quoteTemplate'] = $template(QuoteTemplate::className(), 'iID', 'iQuoteTemplateID');
    $names['quoteTemplateUpgrade']['quoteTemplate'] = $template(QuoteTemplate::className(), 'iID', 'iQuoteTemplateID');
    return $names;
  }

  public static function templates()
  {
    return [
      'songs' => [
        'all' => [
          'label',
        ]
      ],
      'albums' => [
        'all' => [
          'name',
          'artist' => [
            'first_name',
            'last_name'
          ],
          'year',
          'count(songs) AS songsCount',
          'songs' => [
            'id_songs',
            'label'
          ],
        ]
      ]
    ];
  }

  public static function groupBy()
  {
    return [
    ];
  }

  public static function getModel($parent, $needle, $names = [], $index = 'tbl')
  {
    // procedures, steps, comments, author
    $initNames = $names;
    try {
      if (count($names) == 0)
        $names = self::names()[$parent];
      if ($parent == $needle) {
        return $names[$index];
      }
      if (preg_match('/-/', $needle)) {
        $chain = preg_split('/-/', $needle);
        foreach ($chain as $k => $part) {
          $names = $names[$part];
        }
        return $names[$index];
      } else {
        return isset($names[$needle]) && isset($names[$needle][$index]) ? $names[$needle][$index] : false;
      }
    } catch (\Throwable $e) {
//      echo "<pre>";
//      print_r([
//        'parent' => $parent,
//        'needle' => $needle,
//        'index' => $index,
//        'names' => $names
//      ]);
//      echo "</pre>";
//      exit;
      throw new \BadMethodCallException("Can't find chain! Parent: {$parent}, Needle: {$needle}");
    }
    throw new \BadMethodCallException('No data with chain - ' . $needle);
//        if (isset($names[$needle]) && isset($names[$needle][$index]))
//            return $names[$needle][$index];
//        else {
//            foreach ($names as $name) {
//                if (is_array($name))
//                    return self::getModel($parent, $needle, $name, $index);
//            }
//        }
  }
}