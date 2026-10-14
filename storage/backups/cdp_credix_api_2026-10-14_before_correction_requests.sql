-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: cdp_credix_api
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=490 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'CREATE','Country','Created country: Srilanka','{\"code\": \"LK\", \"name\": \"Srilanka\", \"is_active\": true, \"description\": null}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/countries','POST','2026-10-12 10:48:53','2026-10-12 10:48:53'),(2,1,'CREATE','Province','Created province: Western','{\"code\": \"WP\", \"name\": \"Western\", \"is_active\": true, \"country_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/provinces','POST','2026-10-12 10:49:11','2026-10-12 10:49:11'),(3,1,'CREATE','Zonal','Created zonal: Western','{\"code\": \"W\", \"name\": \"Western\", \"is_active\": true, \"province_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/zonals','POST','2026-10-12 10:49:31','2026-10-12 10:49:31'),(4,1,'CREATE','Region','Created region: Colombo','{\"code\": \"COL\", \"name\": \"Colombo\", \"zonal_id\": 1, \"is_active\": true}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/regions','POST','2026-10-12 10:49:50','2026-10-12 10:49:50'),(5,1,'CREATE','Branch','Created branch: Colombo Head','{\"fax\": null, \"city\": \"Colombo\", \"code\": \"H-COL\", \"name\": \"Colombo Head\", \"email\": null, \"zone_id\": 1, \"is_active\": true, \"region_id\": 1, \"branch_type\": \"main\", \"postal_code\": null, \"province_id\": 1, \"opening_date\": \"2026-10-06\", \"address_line1\": \"12, Main rd\", \"address_line2\": null, \"phone_primary\": \"01140074007\", \"is_head_office\": false, \"phone_secondary\": null}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/branches','POST','2026-10-12 10:50:44','2026-10-12 10:50:44'),(6,1,'CREATE','LoanTerm','Created loan term: GENERAL','{\"code\": \"GENERAL\", \"title\": \"GENERAL\", \"is_active\": true, \"description\": null}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-terms','POST','2026-10-12 10:51:17','2026-10-12 10:51:17'),(7,1,'CREATE','LoanType','Created loan type: stand','{\"code\": \"STAND\", \"title\": \"stand\", \"is_active\": true, \"loan_term_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-types','POST','2026-10-12 10:51:40','2026-10-12 10:51:40'),(8,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?page=1','GET','2026-10-12 10:51:47','2026-10-12 10:51:47'),(9,1,'CREATE','LoanProduct','Created loan product: Personal','{\"name\": \"Personal\", \"is_active\": true, \"is_islamic\": false, \"max_amount\": 1000000, \"min_amount\": 10000, \"loan_term_id\": 1, \"loan_type_id\": 1, \"interest_rate\": 10, \"interest_type\": \"flat\", \"is_group_loan\": false, \"penalty_value\": 1000, \"max_term_months\": 60, \"min_term_months\": 6, \"grace_period_days\": 0, \"processing_fee_type\": \"fixed\", \"processing_fee_value\": 1000}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products','POST','2026-10-12 10:53:02','2026-10-12 10:53:02'),(10,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 10:53:11','2026-10-12 10:53:11'),(11,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 10:53:12','2026-10-12 10:53:12'),(12,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-12 10:53:20','2026-10-12 10:53:20'),(13,1,'EMAIL_SENT','Customer','Registration credentials email sent to customer: piranya105@gmail.com','{\"email\": \"piranya105@gmail.com\", \"customer_id\": 1, \"notification_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-12 10:54:56','2026-10-12 10:54:56'),(14,1,'CREATE','Customer','Customer created with associated details','{\"creator_id\": 1, \"customer_code\": \"CUS0001\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-12 10:54:56','2026-10-12 10:54:56'),(15,1,'CREATE','User','Login account created for customer: CUS0001','{\"user_id\": 3, \"username\": \"Piranya\", \"customer_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-12 10:54:56','2026-10-12 10:54:56'),(16,1,'CREATE','CustomerVerification','Customer KYC verification initiated for Customer #1','{\"review_id\": 1, \"customer_id\": 1, \"phone_masked\": \"075*****40\", \"snapshot_version\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers/1/verification/initiate','POST','2026-10-12 10:55:12','2026-10-12 10:55:12'),(17,1,'UPDATE','CustomerVerification','Customer #1 KYC verified with OTP','{\"review_id\": 1, \"customer_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers/1/verification/verify-otp','POST','2026-10-12 10:55:35','2026-10-12 10:55:35'),(18,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"200012436587\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=200012436587','GET','2026-10-12 10:57:18','2026-10-12 10:57:18'),(19,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"200123456789\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=200123456789','GET','2026-10-12 10:57:19','2026-10-12 10:57:19'),(20,1,'CREATE','LoanApplication','Created loan application ID: 1','{\"status\": \"submitted\", \"branch_id\": 1, \"applied_at\": \"2026-10-12T10:57:20.074476Z\", \"applied_by\": 1, \"customer_id\": 1, \"term_months\": 12, \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"application_id\": 1, \"processing_fee\": 1000, \"security_types\": [], \"loan_product_id\": 1, \"recommender_nic\": null, \"recommender_name\": null, \"requested_amount\": 500000, \"recommender_phone\": null, \"recommender_employee_code\": null, \"recommended_by_employee_id\": null}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications','POST','2026-10-12 10:57:20','2026-10-12 10:57:20'),(21,1,'CREATE','Guarantor','Created guarantor: Rifkey','{\"type\": \"guarantor_1\", \"salary\": 100000, \"id_type\": \"NIC\", \"full_name\": \"Rifkey\", \"id_number\": \"200012436587\", \"occupation\": \"SE\", \"customer_id\": \"1\", \"employer_name\": \"CDP\", \"phone_primary\": \"0762570074\", \"employment_status\": \"Employed\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors','POST','2026-10-12 10:57:20','2026-10-12 10:57:20'),(22,1,'CREATE','LoanApplicationGuarantor','Added guarantor ID 1 to loan application ID 1','{\"status\": \"pending\", \"guarantor_id\": 1, \"guarantor_type\": \"guarantor_1\", \"loan_application_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors','POST','2026-10-12 10:57:21','2026-10-12 10:57:21'),(23,1,'CREATE','Guarantor','Created guarantor: Ifham','{\"type\": \"guarantor_2\", \"salary\": 100000, \"id_type\": \"NIC\", \"full_name\": \"Ifham\", \"id_number\": \"200123456789\", \"occupation\": \"SE\", \"customer_id\": \"1\", \"employer_name\": \"CDP\", \"phone_primary\": \"0762570074\", \"employment_status\": \"Employed\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors','POST','2026-10-12 10:57:22','2026-10-12 10:57:22'),(24,1,'CREATE','LoanApplicationGuarantor','Added guarantor ID 2 to loan application ID 1','{\"status\": \"pending\", \"guarantor_id\": 2, \"guarantor_type\": \"guarantor_2\", \"loan_application_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors','POST','2026-10-12 10:57:23','2026-10-12 10:57:23'),(25,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 10:57:24','2026-10-12 10:57:24'),(26,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 10:57:24','2026-10-12 10:57:24'),(27,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 10:58:08','2026-10-12 10:58:08'),(28,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 10:58:09','2026-10-12 10:58:09'),(29,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-12 10:58:19','2026-10-12 10:58:19'),(30,1,'INDEX','Customer','Customers index accessed','{\"count\": 0, \"filters\": {\"search\": \"nil\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=nil','GET','2026-10-12 10:58:39','2026-10-12 10:58:39'),(31,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"Pi\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Pi','GET','2026-10-12 10:58:42','2026-10-12 10:58:42'),(32,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"Pira\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Pira','GET','2026-10-12 10:58:43','2026-10-12 10:58:43'),(33,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"Pira\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-12 10:58:45','2026-10-12 10:58:45'),(34,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"Pirany\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Pirany','GET','2026-10-12 10:58:46','2026-10-12 10:58:46'),(35,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"Pirany\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-12 10:58:47','2026-10-12 10:58:47'),(36,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"Piranya\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Piranya','GET','2026-10-12 10:58:47','2026-10-12 10:58:47'),(37,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"Piranya\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-12 10:58:48','2026-10-12 10:58:48'),(38,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"200012345678\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-12 10:58:52','2026-10-12 10:58:52'),(39,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"200109876543\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=200109876543','GET','2026-10-12 11:00:43','2026-10-12 11:00:43'),(40,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"200234567890\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=200234567890','GET','2026-10-12 11:00:44','2026-10-12 11:00:44'),(41,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"200109876543\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=200109876543','GET','2026-10-12 11:00:55','2026-10-12 11:00:55'),(42,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"200234567890\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=200234567890','GET','2026-10-12 11:00:56','2026-10-12 11:00:56'),(43,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 11:04:00','2026-10-12 11:04:00'),(44,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 11:04:01','2026-10-12 11:04:01'),(45,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 11:08:58','2026-10-12 11:08:58'),(46,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 11:08:59','2026-10-12 11:08:59'),(47,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 11:09:10','2026-10-12 11:09:10'),(48,1,'INDEX','Notification','Notifications index accessed','{\"count\": 1, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-12 11:09:11','2026-10-12 11:09:11'),(49,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 11:09:12','2026-10-12 11:09:12'),(50,1,'INDEX','Notification','Notifications index accessed','{\"count\": 1, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-12 11:09:13','2026-10-12 11:09:13'),(51,1,'UPDATE','Role','Updated role: Super Admin','{\"name\": \"Super Admin\", \"permissions\": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158, 159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181, 182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200, 201, 202, 203, 204, 205]}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/roles/1','PUT','2026-10-12 11:10:13','2026-10-12 11:10:13'),(52,1,'INDEX','Notification','Notifications index accessed','{\"count\": 1, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-12 11:10:25','2026-10-12 11:10:25'),(53,1,'INDEX','Notification','Notifications index accessed','{\"count\": 1, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-12 11:10:29','2026-10-12 11:10:29'),(54,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 11:10:32','2026-10-12 11:10:32'),(55,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-12 11:10:34','2026-10-12 11:10:34'),(56,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-12 11:10:41','2026-10-12 11:10:41'),(57,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-12 11:10:42','2026-10-12 11:10:42'),(58,1,'INDEX','Notification','Notifications index accessed','{\"count\": 1, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-12 11:10:51','2026-10-12 11:10:51'),(59,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-12 11:10:53','2026-10-12 11:10:53'),(60,1,'INDEX','Notification','Notifications index accessed','{\"count\": 1, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-12 11:10:54','2026-10-12 11:10:54'),(61,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-12 11:10:58','2026-10-12 11:10:58'),(62,1,'CREATE','LoanCustomerConfirmation','Customer review link sent for Loan Application #1','{\"customers\": [{\"link_sent\": true, \"customer_id\": 1}], \"loan_application_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications/1/customer-confirmation/send-link','POST','2026-10-12 11:11:08','2026-10-12 11:11:08'),(63,1,'INDEX','Notification','Notifications index accessed','{\"count\": 1, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 03:28:46','2026-10-13 03:28:46'),(64,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-13&start_date=2026-10-01','GET','2026-10-13 03:28:46','2026-10-13 03:28:46'),(65,1,'INDEX','Notification','Notifications index accessed','{\"count\": 1, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 03:28:47','2026-10-13 03:28:47'),(66,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 03:28:48','2026-10-13 03:28:48'),(67,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 03:28:48','2026-10-13 03:28:48'),(68,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 03:29:02','2026-10-13 03:29:02'),(69,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 03:29:02','2026-10-13 03:29:02'),(70,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 03:29:05','2026-10-13 03:29:05'),(71,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 03:29:06','2026-10-13 03:29:06'),(72,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-13 03:29:20','2026-10-13 03:29:20'),(73,1,'UPDATE','LoanType','Updated loan type: standarded Borrowing','{\"code\": \"STAND\", \"title\": \"standarded Borrowing\", \"is_active\": true, \"loan_term_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-types/1','PUT','2026-10-13 03:29:59','2026-10-13 03:29:59'),(74,1,'CREATE','LoanType','Created loan type: Development Fund','{\"code\": \"DF\", \"title\": \"Development Fund\", \"is_active\": true, \"loan_term_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-types','POST','2026-10-13 03:30:45','2026-10-13 03:30:45'),(75,1,'UPDATE','LoanType','Updated loan type: Standarded Borrowing','{\"code\": \"STAND\", \"title\": \"Standarded Borrowing\", \"is_active\": true, \"loan_term_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-types/1','PUT','2026-10-13 03:30:56','2026-10-13 03:30:56'),(76,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 03:31:17','2026-10-13 03:31:17'),(77,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 03:31:18','2026-10-13 03:31:18'),(78,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-13 03:31:28','2026-10-13 03:31:28'),(79,1,'EMAIL_SENT','Customer','Registration credentials email sent to customer: piranyacdp@gmail.com','{\"email\": \"piranyacdp@gmail.com\", \"customer_id\": 2, \"notification_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-13 03:37:51','2026-10-13 03:37:51'),(80,1,'CREATE','Customer','Customer created with associated details','{\"creator_id\": 1, \"customer_code\": \"CUS0002\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-13 03:37:51','2026-10-13 03:37:51'),(81,1,'CREATE','User','Login account created for customer: CUS0002','{\"user_id\": 4, \"username\": \"Niulkshiya\", \"customer_id\": 2}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-13 03:37:51','2026-10-13 03:37:51'),(82,1,'INDEX','Customer','Customers index accessed','{\"count\": 0, \"filters\": {\"search\": \"Ka\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Ka','GET','2026-10-13 03:38:05','2026-10-13 03:38:05'),(83,1,'INDEX','Customer','Customers index accessed','{\"count\": 0, \"filters\": {\"search\": \"Karuna\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Karuna','GET','2026-10-13 03:38:06','2026-10-13 03:38:06'),(84,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"Karuna\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 03:38:07','2026-10-13 03:38:07'),(85,1,'INDEX','Customer','Customers index accessed','{\"count\": 0, \"filters\": {\"search\": \"Karun\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Karun','GET','2026-10-13 03:38:09','2026-10-13 03:38:09'),(86,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"Karun\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 03:38:09','2026-10-13 03:38:09'),(87,1,'SMS_QUEUED','Customer','Registration credentials SMS queued for customer: 0752932640','{\"phone\": \"0752932640\", \"customer_id\": 3, \"notification_id\": 4}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-13 03:41:09','2026-10-13 03:41:09'),(88,1,'CREATE','Customer','Customer created with associated details','{\"creator_id\": 1, \"customer_code\": \"CUS0003\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-13 03:41:09','2026-10-13 03:41:09'),(89,1,'CREATE','User','Login account created for customer: CUS0003','{\"user_id\": 5, \"username\": \"Karuna\", \"customer_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-13 03:41:09','2026-10-13 03:41:09'),(90,1,'CREATE','CustomerVerification','Customer KYC verification initiated for Customer #2','{\"review_id\": 2, \"customer_id\": 2, \"phone_masked\": \"075*****40\", \"snapshot_version\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers/2/verification/initiate','POST','2026-10-13 03:41:28','2026-10-13 03:41:28'),(91,1,'UPDATE','CustomerVerification','Customer #2 KYC verified with OTP','{\"review_id\": 2, \"customer_id\": 2}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers/2/verification/verify-otp','POST','2026-10-13 03:41:46','2026-10-13 03:41:46'),(92,1,'CREATE','CustomerVerification','Customer KYC verification initiated for Customer #3','{\"review_id\": 3, \"customer_id\": 3, \"phone_masked\": \"075*****40\", \"snapshot_version\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers/3/verification/initiate','POST','2026-10-13 03:42:05','2026-10-13 03:42:05'),(93,1,'UPDATE','CustomerVerification','Customer #3 KYC verified with OTP','{\"review_id\": 3, \"customer_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers/3/verification/verify-otp','POST','2026-10-13 03:42:18','2026-10-13 03:42:18'),(94,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"199812345678\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=199812345678','GET','2026-10-13 03:44:22','2026-10-13 03:44:22'),(95,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"199887654321\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=199887654321','GET','2026-10-13 03:44:22','2026-10-13 03:44:22'),(96,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"199812345678\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=199812345678','GET','2026-10-13 03:44:59','2026-10-13 03:44:59'),(97,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"199887654321\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=199887654321','GET','2026-10-13 03:45:00','2026-10-13 03:45:00'),(98,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"199812345678\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=199812345678','GET','2026-10-13 03:45:49','2026-10-13 03:45:49'),(99,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"199887654321\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=199887654321','GET','2026-10-13 03:45:50','2026-10-13 03:45:50'),(100,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"199812345678\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=199812345678','GET','2026-10-13 03:53:23','2026-10-13 03:53:23'),(101,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"199887654321\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=199887654321','GET','2026-10-13 03:53:24','2026-10-13 03:53:24'),(102,1,'CREATE','LoanApplication','Created loan application ID: 2','{\"status\": \"submitted\", \"branch_id\": 1, \"applied_at\": \"2026-10-13T03:53:25.303267Z\", \"applied_by\": 1, \"customer_id\": 2, \"term_months\": 12, \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"application_id\": 2, \"processing_fee\": 1000, \"security_types\": [\"vehicle\"], \"loan_product_id\": 1, \"recommender_nic\": null, \"recommender_name\": null, \"requested_amount\": 500000, \"recommender_phone\": null, \"joint_customer_ids\": [3], \"recommender_employee_code\": null, \"recommended_by_employee_id\": null}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications','POST','2026-10-13 03:53:25','2026-10-13 03:53:25'),(103,1,'CREATE','Document','Uploaded document: WhatsApp Image 2026-10-06 at 12.10.04 PM.jpeg','{\"file_path\": \"uploads/documents/WhatsApp Image 2026-10-06 at 12.10.04 PM.jpeg.jpeg\", \"is_active\": \"1\", \"customer_id\": \"2\", \"uploaded_at\": \"2026-10-13T03:53:26.157385Z\", \"uploaded_by\": 1, \"document_name\": \"WhatsApp Image 2026-10-06 at 12.10.04 PM.jpeg\", \"document_type\": \"vehicle_cr\", \"loan_application_id\": \"2\"}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-10-13 03:53:26','2026-10-13 03:53:26'),(104,1,'CREATE','Guarantor','Created guarantor: Rifkey','{\"type\": \"guarantor_1\", \"salary\": 100000, \"id_type\": \"NIC\", \"full_name\": \"Rifkey\", \"id_number\": \"199812345678\", \"occupation\": \"SE\", \"customer_id\": \"2\", \"date_of_birth\": \"2026-10-01\", \"employer_name\": \"CDP\", \"phone_primary\": \"0762570074\", \"employment_status\": \"Employed\"}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors','POST','2026-10-13 03:53:26','2026-10-13 03:53:26'),(105,1,'CREATE','LoanApplicationGuarantor','Added guarantor ID 3 to loan application ID 2','{\"status\": \"pending\", \"guarantor_id\": 3, \"guarantor_type\": \"guarantor_1\", \"loan_application_id\": 2}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors','POST','2026-10-13 03:53:27','2026-10-13 03:53:27'),(106,1,'CREATE','Document','Uploaded document: WhatsApp Image 2026-10-06 at 12.10.04 PM','{\"file_path\": \"uploads/documents/WhatsApp Image 2026-10-06 at 12.10.04 PM.jpeg\", \"is_active\": \"1\", \"customer_id\": \"2\", \"uploaded_at\": \"2026-10-13T03:53:28.496584Z\", \"uploaded_by\": 1, \"guarantor_id\": \"3\", \"document_name\": \"WhatsApp Image 2026-10-06 at 12.10.04 PM\", \"document_type\": \"bank_statement\", \"loan_application_id\": \"2\"}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-10-13 03:53:28','2026-10-13 03:53:28'),(107,1,'CREATE','Guarantor','Created guarantor: Ifham','{\"type\": \"guarantor_2\", \"salary\": 100000, \"id_type\": \"NIC\", \"full_name\": \"Ifham\", \"id_number\": \"199887654321\", \"occupation\": \"SE\", \"customer_id\": \"2\", \"employer_name\": \"CDP\", \"phone_primary\": \"0762570074\", \"employment_status\": \"Employed\"}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors','POST','2026-10-13 03:53:29','2026-10-13 03:53:29'),(108,1,'CREATE','LoanApplicationGuarantor','Added guarantor ID 4 to loan application ID 2','{\"status\": \"pending\", \"guarantor_id\": 4, \"guarantor_type\": \"guarantor_2\", \"loan_application_id\": 2}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors','POST','2026-10-13 03:53:30','2026-10-13 03:53:30'),(109,1,'CREATE','Document','Uploaded document: Mariyathas Savariyahhii','{\"file_path\": \"uploads/documents/Mariyathas Savariyahhii.pdf\", \"is_active\": \"1\", \"customer_id\": \"2\", \"uploaded_at\": \"2026-10-13T03:53:30.973810Z\", \"uploaded_by\": 1, \"guarantor_id\": \"4\", \"document_name\": \"Mariyathas Savariyahhii\", \"document_type\": \"salary_confirmation_letter\", \"loan_application_id\": \"2\"}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-10-13 03:53:30','2026-10-13 03:53:30'),(110,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 03:53:32','2026-10-13 03:53:32'),(111,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 03:53:32','2026-10-13 03:53:32'),(112,1,'INDEX','Notification','Notifications index accessed','{\"count\": 2, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 03:54:09','2026-10-13 03:54:09'),(113,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 03:54:10','2026-10-13 03:54:10'),(114,1,'INDEX','Notification','Notifications index accessed','{\"count\": 2, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 03:54:10','2026-10-13 03:54:10'),(115,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 03:54:11','2026-10-13 03:54:11'),(116,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?page=1','GET','2026-10-13 04:00:42','2026-10-13 04:00:42'),(117,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 04:00:46','2026-10-13 04:00:46'),(118,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 04:00:46','2026-10-13 04:00:46'),(119,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-13 04:01:00','2026-10-13 04:01:00'),(120,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-13 04:01:01','2026-10-13 04:01:01'),(121,1,'CREATE','LoanCustomerConfirmation','Customer review link sent for Loan Application #2','{\"customers\": [{\"link_sent\": true, \"customer_id\": 2}, {\"link_sent\": true, \"customer_id\": 3}], \"loan_application_id\": 2}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications/2/customer-confirmation/send-link','POST','2026-10-13 04:01:13','2026-10-13 04:01:13'),(122,1,'CREATE','LoanCustomerConfirmation','Customer review link sent for Loan Application #2','{\"customers\": [{\"link_sent\": true, \"customer_id\": 2}, {\"link_sent\": true, \"customer_id\": 3}], \"loan_application_id\": 2}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications/2/customer-confirmation/send-link','POST','2026-10-13 04:01:44','2026-10-13 04:01:44'),(123,1,'INDEX','Notification','Notifications index accessed','{\"count\": 2, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 04:36:22','2026-10-13 04:36:22'),(124,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-13 04:36:24','2026-10-13 04:36:24'),(125,1,'INDEX','Notification','Notifications index accessed','{\"count\": 2, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 04:36:25','2026-10-13 04:36:25'),(126,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-13 04:36:29','2026-10-13 04:36:29'),(127,1,'INDEX','Notification','Notifications index accessed','{\"count\": 2, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 05:05:51','2026-10-13 05:05:51'),(128,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-13&start_date=2026-10-01','GET','2026-10-13 05:05:54','2026-10-13 05:05:54'),(129,1,'INDEX','Notification','Notifications index accessed','{\"count\": 2, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 05:05:56','2026-10-13 05:05:56'),(130,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:05:57','2026-10-13 05:05:57'),(131,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-13&start_date=2026-10-01','GET','2026-10-13 05:06:00','2026-10-13 05:06:00'),(132,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:06:09','2026-10-13 05:06:09'),(133,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:06:16','2026-10-13 05:06:16'),(134,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:06:23','2026-10-13 05:06:23'),(135,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-13 05:06:24','2026-10-13 05:06:24'),(136,1,'EMAIL_SENT','Customer','Registration credentials email sent to customer: cdppiranya@gmail.com','{\"email\": \"cdppiranya@gmail.com\", \"customer_id\": 4, \"notification_id\": 6}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-13 05:10:25','2026-10-13 05:10:25'),(137,1,'CREATE','Customer','Customer created with associated details','{\"creator_id\": 1, \"customer_code\": \"CUS0004\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-13 05:10:25','2026-10-13 05:10:25'),(138,1,'CREATE','User','Login account created for customer: CUS0004','{\"user_id\": 6, \"username\": \"Mithurjan\", \"customer_id\": 4}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-13 05:10:25','2026-10-13 05:10:25'),(139,1,'CREATE','CustomerVerification','Customer KYC verification initiated for Customer #4','{\"review_id\": 4, \"customer_id\": 4, \"phone_masked\": \"075*****40\", \"snapshot_version\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers/4/verification/initiate','POST','2026-10-13 05:12:15','2026-10-13 05:12:15'),(140,1,'UPDATE','CustomerVerification','Customer #4 KYC verified with OTP','{\"review_id\": 4, \"customer_id\": 4}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers/4/verification/verify-otp','POST','2026-10-13 05:13:10','2026-10-13 05:13:10'),(141,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"199612345678\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=199612345678','GET','2026-10-13 05:15:26','2026-10-13 05:15:26'),(142,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"200045678909\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=200045678909','GET','2026-10-13 05:15:28','2026-10-13 05:15:28'),(143,1,'CREATE','LoanApplication','Created loan application ID: 3','{\"status\": \"submitted\", \"branch_id\": 1, \"applied_at\": \"2026-10-13T05:15:30.793828Z\", \"applied_by\": 1, \"customer_id\": 4, \"term_months\": 12, \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"application_id\": 3, \"processing_fee\": 1000, \"security_types\": [\"property_mortgage\"], \"loan_product_id\": 1, \"recommender_nic\": null, \"recommender_name\": null, \"requested_amount\": 500000, \"recommender_phone\": null, \"recommender_employee_code\": null, \"recommended_by_employee_id\": null}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications','POST','2026-10-13 05:15:30','2026-10-13 05:15:30'),(144,1,'CREATE','Document','Uploaded document: WhatsApp Image 2026-10-09 at 9.06.59 AM.jpeg','{\"file_path\": \"uploads/documents/WhatsApp Image 2026-10-09 at 9.06.59 AM.jpeg.jpeg\", \"is_active\": \"1\", \"customer_id\": \"4\", \"uploaded_at\": \"2026-10-13T05:15:32.844610Z\", \"uploaded_by\": 1, \"document_name\": \"WhatsApp Image 2026-10-09 at 9.06.59 AM.jpeg\", \"document_type\": \"property_deed\", \"loan_application_id\": \"3\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-10-13 05:15:32','2026-10-13 05:15:32'),(145,1,'CREATE','Guarantor','Created guarantor: Sulani','{\"type\": \"guarantor_1\", \"salary\": 100000, \"id_type\": \"NIC\", \"full_name\": \"Sulani\", \"id_number\": \"199612345678\", \"occupation\": \"SE\", \"customer_id\": \"4\", \"date_of_birth\": \"2026-10-08\", \"employer_name\": \"CDP\", \"phone_primary\": \"0762570074\", \"employment_status\": \"Employed\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors','POST','2026-10-13 05:15:34','2026-10-13 05:15:34'),(146,1,'CREATE','LoanApplicationGuarantor','Added guarantor ID 5 to loan application ID 3','{\"status\": \"pending\", \"guarantor_id\": 5, \"guarantor_type\": \"guarantor_1\", \"loan_application_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors','POST','2026-10-13 05:15:36','2026-10-13 05:15:36'),(147,1,'CREATE','Document','Uploaded document: WhatsApp Image 2026-10-09 at 9.06.59 AM','{\"file_path\": \"uploads/documents/WhatsApp Image 2026-10-09 at 9.06.59 AM.jpeg\", \"is_active\": \"1\", \"customer_id\": \"4\", \"uploaded_at\": \"2026-10-13T05:15:38.525581Z\", \"uploaded_by\": 1, \"guarantor_id\": \"5\", \"document_name\": \"WhatsApp Image 2026-10-09 at 9.06.59 AM\", \"document_type\": \"nic_copy\", \"loan_application_id\": \"3\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-10-13 05:15:38','2026-10-13 05:15:38'),(148,1,'CREATE','Guarantor','Created guarantor: Changana','{\"type\": \"guarantor_2\", \"salary\": 100000, \"id_type\": \"NIC\", \"full_name\": \"Changana\", \"id_number\": \"200045678909\", \"occupation\": \"SE\", \"customer_id\": \"4\", \"employer_name\": \"CDP\", \"phone_primary\": \"0762570074\", \"employment_status\": \"Employed\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors','POST','2026-10-13 05:15:40','2026-10-13 05:15:40'),(149,1,'CREATE','LoanApplicationGuarantor','Added guarantor ID 6 to loan application ID 3','{\"status\": \"pending\", \"guarantor_id\": 6, \"guarantor_type\": \"guarantor_2\", \"loan_application_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors','POST','2026-10-13 05:15:42','2026-10-13 05:15:42'),(150,1,'CREATE','Document','Uploaded document: WhatsApp Image 2026-10-06 at 12.10.04 PM','{\"file_path\": \"uploads/documents/WhatsApp Image 2026-10-06 at 12.10.04 PM.jpeg\", \"is_active\": \"1\", \"customer_id\": \"4\", \"uploaded_at\": \"2026-10-13T05:15:44.157019Z\", \"uploaded_by\": 1, \"guarantor_id\": \"6\", \"document_name\": \"WhatsApp Image 2026-10-06 at 12.10.04 PM\", \"document_type\": \"nic_copy\", \"loan_application_id\": \"3\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-10-13 05:15:44','2026-10-13 05:15:44'),(151,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:15:46','2026-10-13 05:15:46'),(152,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:15:47','2026-10-13 05:15:47'),(153,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:16:02','2026-10-13 05:16:02'),(154,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 05:16:03','2026-10-13 05:16:03'),(155,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:16:04','2026-10-13 05:16:04'),(156,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 05:16:05','2026-10-13 05:16:05'),(157,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:16:08','2026-10-13 05:16:08'),(158,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:16:09','2026-10-13 05:16:09'),(159,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-13 05:16:18','2026-10-13 05:16:18'),(160,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-13 05:16:22','2026-10-13 05:16:22'),(161,1,'CREATE','LoanCustomerConfirmation','Customer review link sent for Loan Application #3','{\"customers\": [{\"link_sent\": true, \"customer_id\": 4}], \"loan_application_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications/3/customer-confirmation/send-link','POST','2026-10-13 05:16:46','2026-10-13 05:16:46'),(162,1,'INDEX','GroupLoan','Group loans index accessed','{\"count\": 0, \"filters\": {\"status\": \"available\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/group-loans/status/available?page=1&per_page=15','GET','2026-10-13 05:41:38','2026-10-13 05:41:38'),(163,1,'INDEX','GroupLoan','Group loans index accessed','{\"count\": 0, \"filters\": {\"status\": \"available\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/group-loans/status/available?page=1&per_page=15','GET','2026-10-13 05:41:40','2026-10-13 05:41:40'),(164,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:41:48','2026-10-13 05:41:48'),(165,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:41:52','2026-10-13 05:41:52'),(166,1,'INDEX','GroupLoan','Group loans index accessed','{\"count\": 0, \"filters\": {\"status\": \"available\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/group-loans/status/available?page=1&per_page=15','GET','2026-10-13 05:41:53','2026-10-13 05:41:53'),(167,1,'INDEX','GroupLoan','Group loans index accessed','{\"count\": 0, \"filters\": {\"status\": \"available\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/group-loans/status/available?page=1&per_page=15','GET','2026-10-13 05:41:58','2026-10-13 05:41:58'),(168,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?page=1&per_page=20','GET','2026-10-13 05:42:01','2026-10-13 05:42:01'),(169,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:44:14','2026-10-13 05:44:14'),(170,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:44:16','2026-10-13 05:44:16'),(171,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:44:18','2026-10-13 05:44:18'),(172,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 05:44:19','2026-10-13 05:44:19'),(173,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-13 05:44:25','2026-10-13 05:44:25'),(174,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-13 05:44:29','2026-10-13 05:44:29'),(175,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 05:56:55','2026-10-13 05:56:55'),(176,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-13 05:56:58','2026-10-13 05:56:58'),(177,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 05:57:00','2026-10-13 05:57:00'),(178,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-13 05:57:04','2026-10-13 05:57:04'),(179,1,'CREATE','LoanCustomerConfirmation','Customer review link sent for Loan Application #3','{\"customers\": [{\"link_sent\": true, \"customer_id\": 4}], \"loan_application_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications/3/customer-confirmation/send-link','POST','2026-10-13 06:07:22','2026-10-13 06:07:22'),(180,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 06:09:00','2026-10-13 06:09:00'),(181,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 06:09:09','2026-10-13 06:09:09'),(182,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-13 06:09:23','2026-10-13 06:09:23'),(183,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"Mi\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Mi','GET','2026-10-13 06:10:26','2026-10-13 06:10:26'),(184,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"Mithu\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Mithu','GET','2026-10-13 06:10:28','2026-10-13 06:10:28'),(185,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"Mithu\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:10:30','2026-10-13 06:10:30'),(186,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"200312345678\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:10:39','2026-10-13 06:10:39'),(187,1,'INDEX','Customer','Customers index accessed','{\"count\": 0, \"filters\": {\"search\": \"955751221V\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=955751221V','GET','2026-10-13 06:13:16','2026-10-13 06:13:16'),(188,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": true, \"user_id\": 1, \"id_number\": \"955751221V\", \"status_code\": 200}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:13:19','2026-10-13 06:13:19'),(189,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"Mithur\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Mithur','GET','2026-10-13 06:14:04','2026-10-13 06:14:04'),(190,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"Mithur\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:14:09','2026-10-13 06:14:09'),(191,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"Mithurjan\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Mithurjan','GET','2026-10-13 06:14:10','2026-10-13 06:14:10'),(192,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"Mithurjan\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:14:12','2026-10-13 06:14:12'),(193,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"200312345678\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:14:20','2026-10-13 06:14:20'),(194,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"mi\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mi','GET','2026-10-13 06:16:03','2026-10-13 06:16:03'),(195,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"mithu\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithu','GET','2026-10-13 06:16:06','2026-10-13 06:16:06'),(196,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithu\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:16:09','2026-10-13 06:16:09'),(197,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"mithur\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithur','GET','2026-10-13 06:16:10','2026-10-13 06:16:10'),(198,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithur\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:16:12','2026-10-13 06:16:12'),(199,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"200312345678\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:16:20','2026-10-13 06:16:20'),(200,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 06:27:28','2026-10-13 06:27:28'),(201,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 06:27:30','2026-10-13 06:27:30'),(202,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 06:27:32','2026-10-13 06:27:32'),(203,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 06:27:34','2026-10-13 06:27:34'),(204,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-13 06:27:39','2026-10-13 06:27:39'),(205,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-13 06:27:44','2026-10-13 06:27:44'),(206,1,'CREATE','LoanCustomerConfirmation','Customer review link sent for Loan Application #3','{\"customers\": [{\"link_sent\": true, \"customer_id\": 4}], \"loan_application_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications/3/customer-confirmation/send-link','POST','2026-10-13 06:28:24','2026-10-13 06:28:24'),(207,1,'CREATE','LoanCustomerConfirmation','Customer review link sent for Loan Application #3','{\"customers\": [{\"link_sent\": true, \"customer_id\": 4}], \"loan_application_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications/3/customer-confirmation/send-link','POST','2026-10-13 06:28:37','2026-10-13 06:28:37'),(208,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 06:33:14','2026-10-13 06:33:14'),(209,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 06:33:17','2026-10-13 06:33:17'),(210,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 06:33:27','2026-10-13 06:33:27'),(211,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 06:33:35','2026-10-13 06:33:35'),(212,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-13 06:33:43','2026-10-13 06:33:43'),(213,1,'SMS_QUEUED','Customer','Registration credentials SMS queued for customer: 0752932640','{\"phone\": \"0752932640\", \"customer_id\": 5, \"notification_id\": 8}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-13 06:36:57','2026-10-13 06:36:57'),(214,1,'CREATE','Customer','Customer created with associated details','{\"creator_id\": 1, \"customer_code\": \"CUS0005\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-13 06:36:57','2026-10-13 06:36:57'),(215,1,'CREATE','User','Login account created for customer: CUS0005','{\"user_id\": 7, \"username\": \"CUS0005\", \"customer_id\": 5}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-13 06:36:57','2026-10-13 06:36:57'),(216,1,'INDEX','Customer','Customers index accessed','{\"count\": 4, \"filters\": {\"search\": \"m\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=m','GET','2026-10-13 06:38:06','2026-10-13 06:38:06'),(217,1,'INDEX','Customer','Customers index accessed','{\"count\": 0, \"filters\": {\"search\": \"mo\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mo','GET','2026-10-13 06:38:09','2026-10-13 06:38:09'),(218,1,'INDEX','Customer','Customers index accessed','{\"count\": 4, \"filters\": {\"search\": \"m\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=m','GET','2026-10-13 06:38:11','2026-10-13 06:38:11'),(219,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": {\"search\": \"mit\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mit','GET','2026-10-13 06:38:17','2026-10-13 06:38:17'),(220,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": {\"search\": \"mithu\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithu','GET','2026-10-13 06:38:18','2026-10-13 06:38:18'),(221,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithu\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:38:21','2026-10-13 06:38:21'),(222,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"mithur\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithur','GET','2026-10-13 06:38:22','2026-10-13 06:38:22'),(223,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithur\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:38:24','2026-10-13 06:38:24'),(224,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"mithurjan\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithurjan','GET','2026-10-13 06:38:25','2026-10-13 06:38:25'),(225,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithurjan\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:38:27','2026-10-13 06:38:27'),(226,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"200312345678\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:38:35','2026-10-13 06:38:35'),(227,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": {\"search\": \"mithu\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithu','GET','2026-10-13 06:38:49','2026-10-13 06:38:49'),(228,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithu\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:38:55','2026-10-13 06:38:55'),(229,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithurj\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:38:57','2026-10-13 06:38:57'),(230,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"mithurj\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithurj','GET','2026-10-13 06:38:58','2026-10-13 06:38:58'),(231,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithurja\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:39:00','2026-10-13 06:39:00'),(232,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"mithurja\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithurja','GET','2026-10-13 06:39:01','2026-10-13 06:39:01'),(233,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"mithurjan\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithurjan','GET','2026-10-13 06:39:03','2026-10-13 06:39:03'),(234,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithurjan\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:39:05','2026-10-13 06:39:05'),(235,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"200312345678\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 06:39:13','2026-10-13 06:39:13'),(236,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"200312345678\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=200312345678','GET','2026-10-13 06:46:39','2026-10-13 06:46:39'),(237,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"200132547698\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=200132547698','GET','2026-10-13 06:46:41','2026-10-13 06:46:41'),(238,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"200312345678\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=200312345678','GET','2026-10-13 06:46:55','2026-10-13 06:46:55'),(239,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"200132547698\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=200132547698','GET','2026-10-13 06:46:57','2026-10-13 06:46:57'),(240,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": {\"search\": \"mi\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mi','GET','2026-10-13 07:05:41','2026-10-13 07:05:41'),(241,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": {\"search\": \"mithu\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithu','GET','2026-10-13 07:05:46','2026-10-13 07:05:46'),(242,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithu\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 07:05:49','2026-10-13 07:05:49'),(243,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"mithur\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithur','GET','2026-10-13 07:05:50','2026-10-13 07:05:50'),(244,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithur\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 07:05:52','2026-10-13 07:05:52'),(245,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"mithurj\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithurj','GET','2026-10-13 07:05:58','2026-10-13 07:05:58'),(246,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithurj\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 07:06:00','2026-10-13 07:06:00'),(247,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"mithurja\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithurja','GET','2026-10-13 07:06:01','2026-10-13 07:06:01'),(248,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithurja\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 07:06:03','2026-10-13 07:06:03'),(249,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"mithurjan\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=mithurjan','GET','2026-10-13 07:06:04','2026-10-13 07:06:04'),(250,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"mithurjan\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 07:06:06','2026-10-13 07:06:06'),(251,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"200312345678\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 07:06:16','2026-10-13 07:06:16'),(252,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 07:06:18','2026-10-13 07:06:18'),(253,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 07:06:20','2026-10-13 07:06:20'),(254,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 07:06:27','2026-10-13 07:06:27'),(255,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 07:06:28','2026-10-13 07:06:28'),(256,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-13 07:06:36','2026-10-13 07:06:36'),(257,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-13 07:06:40','2026-10-13 07:06:40'),(258,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 07:06:53','2026-10-13 07:06:53'),(259,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 07:07:02','2026-10-13 07:07:02'),(260,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-13 07:07:10','2026-10-13 07:07:10'),(261,1,'INDEX','Customer','Customers index accessed','{\"count\": 4, \"filters\": {\"search\": \"M\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=M','GET','2026-10-13 07:09:12','2026-10-13 07:09:12'),(262,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": {\"search\": \"Mi\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Mi','GET','2026-10-13 07:09:15','2026-10-13 07:09:15'),(263,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": {\"search\": \"Mithu\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Mithu','GET','2026-10-13 07:09:18','2026-10-13 07:09:18'),(264,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"Mithu\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 07:09:20','2026-10-13 07:09:20'),(265,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": {\"search\": \"Mithur\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=Mithur','GET','2026-10-13 07:09:21','2026-10-13 07:09:21'),(266,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"Mithur\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 07:09:23','2026-10-13 07:09:23'),(267,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"200312345678\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-13 07:09:36','2026-10-13 07:09:36'),(268,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 07:54:43','2026-10-13 07:54:43'),(269,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 07:54:47','2026-10-13 07:54:47'),(270,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 08:16:34','2026-10-13 08:16:34'),(271,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-13&start_date=2026-10-01','GET','2026-10-13 08:16:35','2026-10-13 08:16:35'),(272,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 08:16:36','2026-10-13 08:16:36'),(273,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 08:29:30','2026-10-13 08:29:30'),(274,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 08:29:31','2026-10-13 08:29:31'),(275,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 08:44:37','2026-10-13 08:44:37'),(276,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 08:44:38','2026-10-13 08:44:38'),(277,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?page=1&per_page=20','GET','2026-10-13 08:45:57','2026-10-13 08:45:57'),(278,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 09:03:23','2026-10-13 09:03:23'),(279,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?page=1&per_page=20','GET','2026-10-13 09:03:24','2026-10-13 09:03:24'),(280,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 09:03:24','2026-10-13 09:03:24'),(281,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 09:10:34','2026-10-13 09:10:34'),(282,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 09:10:35','2026-10-13 09:10:35'),(283,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 09:23:13','2026-10-13 09:23:13'),(284,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 09:23:13','2026-10-13 09:23:13'),(285,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 09:23:14','2026-10-13 09:23:14'),(286,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 09:23:15','2026-10-13 09:23:15'),(287,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 09:33:25','2026-10-13 09:33:25'),(288,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 09:33:27','2026-10-13 09:33:27'),(289,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 09:33:28','2026-10-13 09:33:28'),(290,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-13 09:33:29','2026-10-13 09:33:29'),(291,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?page=1','GET','2026-10-13 09:35:44','2026-10-13 09:35:44'),(292,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?page=1','GET','2026-10-13 09:40:22','2026-10-13 09:40:22'),(293,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 09:48:56','2026-10-13 09:48:56'),(294,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 09:48:59','2026-10-13 09:48:59'),(295,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 09:56:39','2026-10-13 09:56:39'),(296,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-13&start_date=2026-10-01','GET','2026-10-13 09:56:40','2026-10-13 09:56:40'),(297,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 09:56:41','2026-10-13 09:56:41'),(298,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-10-13 09:58:52','2026-10-13 09:58:52'),(299,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-10-13 09:58:53','2026-10-13 09:58:53'),(300,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-10-13 09:58:57','2026-10-13 09:58:57'),(301,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-10-13 09:58:58','2026-10-13 09:58:58'),(302,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 10:06:23','2026-10-13 10:06:23'),(303,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-10-13 10:06:24','2026-10-13 10:06:24'),(304,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-10-13 10:06:25','2026-10-13 10:06:25'),(305,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-13 10:06:28','2026-10-13 10:06:28'),(306,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-10-13 10:06:29','2026-10-13 10:06:29'),(307,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-10-13 10:06:31','2026-10-13 10:06:31'),(308,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 04:38:11','2026-10-14 04:38:11'),(309,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-14&start_date=2026-10-01','GET','2026-10-14 04:38:12','2026-10-14 04:38:12'),(310,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 04:38:12','2026-10-14 04:38:12'),(311,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 05:12:51','2026-10-14 05:12:51'),(312,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 05:12:52','2026-10-14 05:12:52'),(313,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-14 05:12:57','2026-10-14 05:12:57'),(314,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-14 05:12:59','2026-10-14 05:12:59'),(315,1,'UPDATE','LoanApplication','Loan application ID: 3 cancelled','{\"loan_application_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications/3/cancel','PATCH','2026-10-14 05:13:23','2026-10-14 05:13:23'),(316,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 05:22:48','2026-10-14 05:22:48'),(317,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 05:22:48','2026-10-14 05:22:48'),(318,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 05:22:55','2026-10-14 05:22:55'),(319,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 05:22:56','2026-10-14 05:22:56'),(320,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:38:38','2026-10-14 05:38:38'),(321,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-14&start_date=2026-10-01','GET','2026-10-14 05:38:38','2026-10-14 05:38:38'),(322,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:38:40','2026-10-14 05:38:40'),(323,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 05:38:40','2026-10-14 05:38:40'),(324,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 05:38:41','2026-10-14 05:38:41'),(325,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 05:38:45','2026-10-14 05:38:45'),(326,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 05:38:47','2026-10-14 05:38:47'),(327,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:39:00','2026-10-14 05:39:00'),(328,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 05:39:01','2026-10-14 05:39:01'),(329,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:39:01','2026-10-14 05:39:01'),(330,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 05:39:03','2026-10-14 05:39:03'),(331,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 05:39:09','2026-10-14 05:39:09'),(332,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 05:39:10','2026-10-14 05:39:10'),(333,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:39:15','2026-10-14 05:39:15'),(334,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 05:39:15','2026-10-14 05:39:15'),(335,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:39:16','2026-10-14 05:39:16'),(336,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 05:39:16','2026-10-14 05:39:16'),(337,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 05:39:26','2026-10-14 05:39:26'),(338,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 05:39:27','2026-10-14 05:39:27'),(339,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:40:05','2026-10-14 05:40:05'),(340,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 05:40:07','2026-10-14 05:40:07'),(341,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:40:08','2026-10-14 05:40:08'),(342,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 05:40:09','2026-10-14 05:40:09'),(343,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:40:32','2026-10-14 05:40:32'),(344,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-14&start_date=2026-10-01','GET','2026-10-14 05:40:33','2026-10-14 05:40:33'),(345,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:40:34','2026-10-14 05:40:34'),(346,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 05:40:36','2026-10-14 05:40:36'),(347,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 05:40:37','2026-10-14 05:40:37'),(348,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-14 05:40:42','2026-10-14 05:40:42'),(349,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-14 05:40:43','2026-10-14 05:40:43'),(350,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:42:17','2026-10-14 05:42:17'),(351,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-14 05:42:19','2026-10-14 05:42:19'),(352,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:42:19','2026-10-14 05:42:19'),(353,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:42:24','2026-10-14 05:42:24'),(354,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-14 05:42:25','2026-10-14 05:42:25'),(355,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 05:42:25','2026-10-14 05:42:25'),(356,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-14 05:42:27','2026-10-14 05:42:27'),(357,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 06:19:34','2026-10-14 06:19:34'),(358,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-14 06:19:37','2026-10-14 06:19:37'),(359,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 06:19:38','2026-10-14 06:19:38'),(360,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-14 06:19:41','2026-10-14 06:19:41'),(361,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 06:20:02','2026-10-14 06:20:02'),(362,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-14 06:20:04','2026-10-14 06:20:04'),(363,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 06:20:05','2026-10-14 06:20:05'),(364,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-14 06:20:08','2026-10-14 06:20:08'),(365,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 06:20:30','2026-10-14 06:20:30'),(366,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-14 06:20:33','2026-10-14 06:20:33'),(367,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 06:20:34','2026-10-14 06:20:34'),(368,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-14 06:20:36','2026-10-14 06:20:36'),(369,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://localhost:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 06:21:41','2026-10-14 06:21:41'),(370,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-14 06:21:44','2026-10-14 06:21:44'),(371,1,'INDEX','Notification','Notifications index accessed','{\"count\": 3, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://localhost:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 06:21:45','2026-10-14 06:21:45'),(372,1,'INDEX','Document','Documents index accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-14 06:21:47','2026-10-14 06:21:47'),(373,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:24:25','2026-10-14 06:24:25'),(374,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:24:26','2026-10-14 06:24:26'),(375,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-14 06:24:38','2026-10-14 06:24:38'),(376,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"3\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=3&per_page=200','GET','2026-10-14 06:24:40','2026-10-14 06:24:40'),(377,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:24:47','2026-10-14 06:24:47'),(378,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:24:48','2026-10-14 06:24:48'),(379,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 06:24:54','2026-10-14 06:24:54'),(380,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 06:24:57','2026-10-14 06:24:57'),(381,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": {\"search\": \"APP-HCOL-2610130001\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?per_page=100&search=APP-HCOL-2610130001&status=submitted%2Creview_failed%2Creviewed%2Creverify%2Creopened','GET','2026-10-14 06:25:53','2026-10-14 06:25:53'),(382,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 06:25:59','2026-10-14 06:25:59'),(383,1,'INDEX','LoanApplicationGuarantor','Loan application guarantors index accessed','{\"count\": 2, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-application-guarantors?loan_application_id=2&page=1','GET','2026-10-14 06:26:01','2026-10-14 06:26:01'),(384,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:27:43','2026-10-14 06:27:43'),(385,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:27:46','2026-10-14 06:27:46'),(386,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:27:56','2026-10-14 06:27:56'),(387,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:28:05','2026-10-14 06:28:05'),(388,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:28:12','2026-10-14 06:28:12'),(389,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:28:13','2026-10-14 06:28:13'),(390,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:28:19','2026-10-14 06:28:19'),(391,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:28:20','2026-10-14 06:28:20'),(392,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 06:28:26','2026-10-14 06:28:26'),(393,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 06:28:28','2026-10-14 06:28:28'),(394,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:31:06','2026-10-14 06:31:06'),(395,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:31:16','2026-10-14 06:31:16'),(396,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-14 06:32:23','2026-10-14 06:32:23'),(397,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": {\"search\": \"Mit\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/customers?page=1&search=Mit','GET','2026-10-14 06:34:45','2026-10-14 06:34:45'),(398,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": {\"search\": \"Mithu\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/customers?page=1&search=Mithu','GET','2026-10-14 06:34:46','2026-10-14 06:34:46'),(399,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"Mithu\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/cdp/verify-customer','POST','2026-10-14 06:34:49','2026-10-14 06:34:49'),(400,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": null, \"success\": false, \"user_id\": 1, \"id_number\": \"200312345678\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/cdp/verify-customer','POST','2026-10-14 06:35:01','2026-10-14 06:35:01'),(401,1,'CREATE','CustomerVerification','Customer KYC verification initiated for Customer #4','{\"review_id\": 5, \"customer_id\": 4, \"phone_masked\": \"075*****40\", \"snapshot_version\": 2}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/customers/4/verification/initiate','POST','2026-10-14 06:35:22','2026-10-14 06:35:22'),(402,1,'UPDATE','CustomerVerification','Customer #4 KYC verified with OTP','{\"review_id\": 5, \"customer_id\": 4}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/customers/4/verification/verify-otp','POST','2026-10-14 06:35:37','2026-10-14 06:35:37'),(403,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"123456789012\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/guarantors?per_page=50&search=123456789012','GET','2026-10-14 06:37:29','2026-10-14 06:37:29'),(404,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"987654321232\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/guarantors?per_page=50&search=987654321232','GET','2026-10-14 06:37:31','2026-10-14 06:37:31'),(405,1,'CREATE','LoanApplication','Created loan application ID: 4','{\"status\": \"submitted\", \"branch_id\": 1, \"applied_at\": \"2026-10-14T06:37:33.283886Z\", \"applied_by\": 1, \"customer_id\": 4, \"term_months\": 12, \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"application_id\": 4, \"processing_fee\": 1000, \"security_types\": [\"property_mortgage\"], \"loan_product_id\": 1, \"recommender_nic\": null, \"recommender_name\": null, \"requested_amount\": 500000, \"recommender_phone\": null, \"recommender_employee_code\": null, \"recommended_by_employee_id\": null}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications','POST','2026-10-14 06:37:33','2026-10-14 06:37:33'),(406,1,'CREATE','Document','Uploaded document: DOCUMENT Check list_260709_184626.pdf','{\"file_path\": \"uploads/documents/DOCUMENT Check list_260709_184626.pdf.pdf\", \"is_active\": \"1\", \"customer_id\": \"4\", \"uploaded_at\": \"2026-10-14T06:37:35.395608Z\", \"uploaded_by\": 1, \"document_name\": \"DOCUMENT Check list_260709_184626.pdf\", \"document_type\": \"valuation_report\", \"loan_application_id\": \"4\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents','POST','2026-10-14 06:37:35','2026-10-14 06:37:35'),(407,1,'CREATE','Document','Uploaded document: DOCUMENT Check list_260709_184626.pdf','{\"file_path\": \"uploads/documents/DOCUMENT Check list_260709_184626.pdf.pdf\", \"is_active\": \"1\", \"customer_id\": \"4\", \"uploaded_at\": \"2026-10-14T06:37:37.882506Z\", \"uploaded_by\": 1, \"document_name\": \"DOCUMENT Check list_260709_184626.pdf\", \"document_type\": \"property_deed\", \"loan_application_id\": \"4\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents','POST','2026-10-14 06:37:37','2026-10-14 06:37:37'),(408,1,'CREATE','Guarantor','Created guarantor: Karuna','{\"type\": \"guarantor_1\", \"salary\": 100000, \"id_type\": \"NIC\", \"full_name\": \"Karuna\", \"id_number\": \"123456789012\", \"occupation\": \"SE\", \"customer_id\": \"4\", \"date_of_birth\": \"2026-10-02\", \"employer_name\": \"CDP\", \"phone_primary\": \"0762570074\", \"employment_status\": \"Employed\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/guarantors','POST','2026-10-14 06:37:39','2026-10-14 06:37:39'),(409,1,'CREATE','LoanApplicationGuarantor','Added guarantor ID 7 to loan application ID 4','{\"status\": \"pending\", \"guarantor_id\": 7, \"guarantor_type\": \"guarantor_1\", \"loan_application_id\": 4}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-application-guarantors','POST','2026-10-14 06:37:41','2026-10-14 06:37:41'),(410,1,'CREATE','Guarantor','Created guarantor: Sahi','{\"type\": \"guarantor_2\", \"salary\": 100000, \"id_type\": \"NIC\", \"full_name\": \"Sahi\", \"id_number\": \"987654321232\", \"occupation\": \"SE\", \"customer_id\": \"4\", \"employer_name\": \"CDP\", \"phone_primary\": \"0762570074\", \"employment_status\": \"Employed\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/guarantors','POST','2026-10-14 06:37:43','2026-10-14 06:37:43'),(411,1,'CREATE','LoanApplicationGuarantor','Added guarantor ID 8 to loan application ID 4','{\"status\": \"pending\", \"guarantor_id\": 8, \"guarantor_type\": \"guarantor_2\", \"loan_application_id\": 4}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-application-guarantors','POST','2026-10-14 06:37:45','2026-10-14 06:37:45'),(412,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:37:48','2026-10-14 06:37:48'),(413,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:37:49','2026-10-14 06:37:49'),(414,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:37:54','2026-10-14 06:37:54'),(415,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:37:55','2026-10-14 06:37:55'),(416,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"4\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=4&per_page=200','GET','2026-10-14 06:38:03','2026-10-14 06:38:03'),(417,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"4\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=4&per_page=200','GET','2026-10-14 06:38:06','2026-10-14 06:38:06'),(418,1,'UPDATE','LoanApplication','Loan application ID: 4 reviewed','{\"loan_application_id\": 4}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications/4/review','PATCH','2026-10-14 06:38:44','2026-10-14 06:38:44'),(419,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"4\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=4&per_page=200','GET','2026-10-14 06:38:47','2026-10-14 06:38:47'),(420,1,'UPDATE','LoanApplication','Loan application ID: 4 verified','{\"loan_application_id\": 4}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications/4/verify','PATCH','2026-10-14 06:38:57','2026-10-14 06:38:57'),(421,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"4\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=4&per_page=200','GET','2026-10-14 06:38:59','2026-10-14 06:38:59'),(422,1,'UPDATE','LoanApplication','Loan application ID: 4 approved','{\"loan_application_id\": 4}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications/4/approve','PATCH','2026-10-14 06:39:49','2026-10-14 06:39:49'),(423,1,'UPDATE','LoanApplication','Loan application ID: 4 offer accepted','{\"decline_reason\": null, \"loan_application_id\": 4}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications/4/accept-offer','PATCH','2026-10-14 06:40:04','2026-10-14 06:40:04'),(424,1,'CREATE','LoanCustomerConfirmation','Customer review link sent for Loan Application #4','{\"customers\": [{\"link_sent\": true, \"customer_id\": 4}], \"loan_application_id\": 4}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications/4/customer-confirmation/send-link','POST','2026-10-14 06:40:18','2026-10-14 06:40:18'),(425,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:51:06','2026-10-14 06:51:06'),(426,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:51:09','2026-10-14 06:51:09'),(427,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:51:10','2026-10-14 06:51:10'),(428,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 06:51:11','2026-10-14 06:51:11'),(429,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-14 06:51:34','2026-10-14 06:51:34'),(430,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:12:41','2026-10-14 07:12:41'),(431,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:12:42','2026-10-14 07:12:42'),(432,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 07:12:53','2026-10-14 07:12:53'),(433,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 07:12:56','2026-10-14 07:12:56'),(434,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:12:59','2026-10-14 07:12:59'),(435,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:13:00','2026-10-14 07:13:00'),(436,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"4\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=4&per_page=200','GET','2026-10-14 07:13:06','2026-10-14 07:13:06'),(437,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"4\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=4&per_page=200','GET','2026-10-14 07:13:10','2026-10-14 07:13:10'),(438,1,'INDEX','Notification','Notifications index accessed','{\"count\": 4, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 07:23:58','2026-10-14 07:23:58'),(439,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"4\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=4&per_page=200','GET','2026-10-14 07:24:01','2026-10-14 07:24:01'),(440,1,'INDEX','Notification','Notifications index accessed','{\"count\": 4, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 07:24:03','2026-10-14 07:24:03'),(441,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"4\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=4&per_page=200','GET','2026-10-14 07:24:07','2026-10-14 07:24:07'),(442,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:34:13','2026-10-14 07:34:13'),(443,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:34:14','2026-10-14 07:34:14'),(444,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 07:34:21','2026-10-14 07:34:21'),(445,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"2\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=2&per_page=200','GET','2026-10-14 07:34:25','2026-10-14 07:34:25'),(446,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:34:48','2026-10-14 07:34:48'),(447,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 4, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:34:58','2026-10-14 07:34:58'),(448,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-14 07:35:12','2026-10-14 07:35:12'),(449,1,'SMS_QUEUED','Customer','Registration credentials SMS queued for customer: 0752932640','{\"phone\": \"0752932640\", \"customer_id\": 6, \"notification_id\": 10}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-14 07:38:59','2026-10-14 07:38:59'),(450,1,'CREATE','Customer','Customer created with associated details','{\"creator_id\": 1, \"customer_code\": \"CUS0006\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-14 07:38:59','2026-10-14 07:38:59'),(451,1,'CREATE','User','Login account created for customer: CUS0006','{\"user_id\": 8, \"username\": \"Kari\", \"customer_id\": 6}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-10-14 07:38:59','2026-10-14 07:38:59'),(452,1,'CREATE','CustomerVerification','Customer KYC verification initiated for Customer #6','{\"review_id\": 6, \"customer_id\": 6, \"phone_masked\": \"075*****40\", \"snapshot_version\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers/6/verification/initiate','POST','2026-10-14 07:39:10','2026-10-14 07:39:10'),(453,1,'UPDATE','CustomerVerification','Customer #6 KYC verified with OTP','{\"review_id\": 6, \"customer_id\": 6}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers/6/verification/verify-otp','POST','2026-10-14 07:39:26','2026-10-14 07:39:26'),(454,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 1, \"filters\": {\"search\": \"199612345678\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=199612345678','GET','2026-10-14 07:40:50','2026-10-14 07:40:50'),(455,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"199512345678\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=199512345678','GET','2026-10-14 07:40:52','2026-10-14 07:40:52'),(456,1,'CREATE','LoanApplication','Created loan application ID: 5','{\"status\": \"submitted\", \"branch_id\": 1, \"applied_at\": \"2026-10-14T07:40:54.183855Z\", \"applied_by\": 1, \"customer_id\": 6, \"term_months\": 12, \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"application_id\": 5, \"processing_fee\": 1000, \"security_types\": [\"vehicle\"], \"loan_product_id\": 1, \"recommender_nic\": null, \"recommender_name\": null, \"requested_amount\": 500000, \"recommender_phone\": null, \"recommender_employee_code\": null, \"recommended_by_employee_id\": null}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications','POST','2026-10-14 07:40:54','2026-10-14 07:40:54'),(457,1,'CREATE','Document','Uploaded document: mermaid-diagram.png','{\"file_path\": \"uploads/documents/mermaid-diagram.png.png\", \"is_active\": \"1\", \"customer_id\": \"6\", \"uploaded_at\": \"2026-10-14T07:40:56.358542Z\", \"uploaded_by\": 1, \"document_name\": \"mermaid-diagram.png\", \"document_type\": \"vehicle_cr\", \"loan_application_id\": \"5\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-10-14 07:40:56','2026-10-14 07:40:56'),(458,1,'CREATE','Guarantor','Created guarantor: Vishnu','{\"type\": \"guarantor_1\", \"salary\": 1000000, \"id_type\": \"NIC\", \"full_name\": \"Vishnu\", \"id_number\": \"199612345678\", \"occupation\": \"SE\", \"customer_id\": \"6\", \"employer_name\": \"CDP\", \"phone_primary\": \"0762570074\", \"employment_status\": \"Employed\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors','POST','2026-10-14 07:40:58','2026-10-14 07:40:58'),(459,1,'CREATE','LoanApplicationGuarantor','Added guarantor ID 9 to loan application ID 5','{\"status\": \"pending\", \"guarantor_id\": 9, \"guarantor_type\": \"guarantor_1\", \"loan_application_id\": 5}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors','POST','2026-10-14 07:41:00','2026-10-14 07:41:00'),(460,1,'CREATE','Guarantor','Created guarantor: Kamal','{\"type\": \"guarantor_2\", \"salary\": 100000, \"id_type\": \"NIC\", \"full_name\": \"Kamal\", \"id_number\": \"199512345678\", \"occupation\": \"SE\", \"customer_id\": \"6\", \"employer_name\": \"CDP\", \"phone_primary\": \"07632570074\", \"employment_status\": \"Employed\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors','POST','2026-10-14 07:41:02','2026-10-14 07:41:02'),(461,1,'CREATE','LoanApplicationGuarantor','Added guarantor ID 10 to loan application ID 5','{\"status\": \"pending\", \"guarantor_id\": 10, \"guarantor_type\": \"guarantor_2\", \"loan_application_id\": 5}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors','POST','2026-10-14 07:41:04','2026-10-14 07:41:04'),(462,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 5, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:41:06','2026-10-14 07:41:06'),(463,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 5, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:41:08','2026-10-14 07:41:08'),(464,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 5, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:41:12','2026-10-14 07:41:12'),(465,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 5, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:41:14','2026-10-14 07:41:14'),(466,1,'INDEX','Document','Documents index accessed','{\"count\": 1, \"filters\": {\"loan_application_id\": \"5\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=5&per_page=200','GET','2026-10-14 07:41:21','2026-10-14 07:41:21'),(467,1,'INDEX','Document','Documents index accessed','{\"count\": 1, \"filters\": {\"loan_application_id\": \"5\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=5&per_page=200','GET','2026-10-14 07:41:24','2026-10-14 07:41:24'),(468,1,'INDEX','Notification','Notifications index accessed','{\"count\": 5, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 07:41:48','2026-10-14 07:41:48'),(469,1,'INDEX','Document','Documents index accessed','{\"count\": 1, \"filters\": {\"loan_application_id\": \"5\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=5&per_page=200','GET','2026-10-14 07:41:50','2026-10-14 07:41:50'),(470,1,'INDEX','Notification','Notifications index accessed','{\"count\": 5, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 07:41:52','2026-10-14 07:41:52'),(471,1,'INDEX','Document','Documents index accessed','{\"count\": 1, \"filters\": {\"loan_application_id\": \"5\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=5&per_page=200','GET','2026-10-14 07:41:55','2026-10-14 07:41:55'),(472,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 5, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:42:06','2026-10-14 07:42:06'),(473,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 5, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:42:08','2026-10-14 07:42:08'),(474,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 5, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:42:10','2026-10-14 07:42:10'),(475,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 5, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-14 07:42:11','2026-10-14 07:42:11'),(476,1,'INDEX','Document','Documents index accessed','{\"count\": 1, \"filters\": {\"loan_application_id\": \"5\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=5&per_page=200','GET','2026-10-14 07:42:34','2026-10-14 07:42:34'),(477,1,'INDEX','Document','Documents index accessed','{\"count\": 1, \"filters\": {\"loan_application_id\": \"5\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=5&per_page=200','GET','2026-10-14 07:42:38','2026-10-14 07:42:38'),(478,1,'INDEX','Notification','Notifications index accessed','{\"count\": 5, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 08:15:59','2026-10-14 08:15:59'),(479,1,'INDEX','Document','Documents index accessed','{\"count\": 1, \"filters\": {\"loan_application_id\": \"5\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=5&per_page=200','GET','2026-10-14 08:16:02','2026-10-14 08:16:02'),(480,1,'INDEX','Notification','Notifications index accessed','{\"count\": 5, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-14 08:16:03','2026-10-14 08:16:03'),(481,1,'INDEX','Document','Documents index accessed','{\"count\": 1, \"filters\": {\"loan_application_id\": \"5\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=5&per_page=200','GET','2026-10-14 08:16:05','2026-10-14 08:16:05'),(482,1,'UPDATE','LoanApplication','Loan application ID: 5 reviewed','{\"loan_application_id\": 5}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications/5/review','PATCH','2026-10-14 08:16:21','2026-10-14 08:16:21'),(483,1,'INDEX','Document','Documents index accessed','{\"count\": 1, \"filters\": {\"loan_application_id\": \"5\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=5&per_page=200','GET','2026-10-14 08:16:24','2026-10-14 08:16:24'),(484,1,'UPDATE','LoanApplication','Loan application ID: 5 verified','{\"loan_application_id\": 5}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications/5/verify','PATCH','2026-10-14 08:16:33','2026-10-14 08:16:33'),(485,1,'INDEX','Document','Documents index accessed','{\"count\": 1, \"filters\": {\"loan_application_id\": \"5\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/documents?loan_application_id=5&per_page=200','GET','2026-10-14 08:16:36','2026-10-14 08:16:36'),(486,1,'UPDATE','LoanApplication','Loan application ID: 5 approved','{\"loan_application_id\": 5}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications/5/approve','PATCH','2026-10-14 08:16:49','2026-10-14 08:16:49'),(487,1,'UPDATE','LoanApplication','Loan application ID: 5 offer on_hold','{\"decline_reason\": null, \"loan_application_id\": 5}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications/5/hold-offer','PATCH','2026-10-14 08:17:05','2026-10-14 08:17:05'),(488,1,'UPDATE','LoanApplication','Loan application ID: 5 offer accepted','{\"decline_reason\": null, \"loan_application_id\": 5}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications/5/accept-offer','PATCH','2026-10-14 08:17:17','2026-10-14 08:17:17'),(489,1,'CREATE','LoanCustomerConfirmation','Customer review link sent for Loan Application #5','{\"customers\": [{\"link_sent\": true, \"customer_id\": 6}], \"loan_application_id\": 5}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://localhost:8000/api/v1/loan-applications/5/customer-confirmation/send-link','POST','2026-10-14 08:17:29','2026-10-14 08:17:29');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loan_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requested_amount` decimal(15,2) NOT NULL,
  `purpose` text COLLATE utf8mb4_unicode_ci,
  `repayment_period_months` int DEFAULT NULL,
  `monthly_repayment_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `applications_application_no_unique` (`application_no`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applications`
--

LOCK TABLES `applications` WRITE;
/*!40000 ALTER TABLE `applications` DISABLE KEYS */;
INSERT INTO `applications` VALUES (1,'APP-HCOL-2610120001','loan','Colombo Head',NULL,500000.00,NULL,12,NULL,'pending','2026-10-12 10:57:20','2026-10-12 10:57:20'),(2,'APP-HCOL-2610130001','loan','Colombo Head',NULL,500000.00,NULL,12,NULL,'pending','2026-10-13 03:53:25','2026-10-13 03:53:25'),(3,'APP-HCOL-2610130002','loan','Colombo Head',NULL,500000.00,NULL,12,NULL,'cancelled','2026-10-13 05:15:30','2026-10-14 05:13:23'),(4,'APP-HCOL-2610140001','loan','Colombo Head',NULL,500000.00,NULL,12,NULL,'approved','2026-10-14 06:37:33','2026-10-14 06:40:04'),(5,'APP-HCOL-2610140002','loan','Colombo Head',NULL,500000.00,NULL,12,NULL,'approved','2026-10-14 07:40:54','2026-10-14 08:17:17');
/*!40000 ALTER TABLE `applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint unsigned DEFAULT NULL,
  `province_id` bigint unsigned NOT NULL,
  `zone_id` bigint unsigned NOT NULL,
  `region_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_primary` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_secondary` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opening_date` date NOT NULL,
  `branch_type` enum('main','city','satellite','mobile') COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_head_office` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_code_unique` (`code`),
  KEY `branches_group_id_foreign` (`group_id`),
  KEY `branches_province_id_foreign` (`province_id`),
  KEY `branches_zone_id_foreign` (`zone_id`),
  KEY `branches_region_id_foreign` (`region_id`),
  CONSTRAINT `branches_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `branches_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branches_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branches_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zonals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,NULL,1,1,1,'Colombo Head','H-COL','12, Main rd',NULL,'Colombo',NULL,'01140074007',NULL,NULL,NULL,'2026-10-06','main',NULL,NULL,1,0,'2026-10-12 10:50:44','2026-10-12 10:50:44');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('cdp-credix-cache-5c785c036466adea360111aa28563bfd556b5fba','i:2;',1791966013),('cdp-credix-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1791966013;',1791966013),('cdp-credix-cache-94d92f976fd06fd3e8cf53ec4e03d646','i:1;',1791952747),('cdp-credix-cache-94d92f976fd06fd3e8cf53ec4e03d646:timer','i:1791952747;',1791952747),('cdp-credix-cache-9facce207cfe500b952220711d53629a','i:1;',1791963626),('cdp-credix-cache-9facce207cfe500b952220711d53629a:timer','i:1791963626;',1791963626),('cdp-credix-cache-cdea0c6cf988a96b71ac5ce10d938e62','i:1;',1791965907),('cdp-credix-cache-cdea0c6cf988a96b71ac5ce10d938e62:timer','i:1791965907;',1791965907),('cdp-credix-cache-dialog_sms_token','s:341:\"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MTcwODcsInVzZXJuYW1lIjoiQ0RQRVBWVExURCIsIm1vYmlsZSI6NzQ0MTIzMDk5LCJlbWFpbCI6IklUX0Rldk9wc0BjZHAubGsiLCJjdXN0b21lcl9yb2xlIjowLCJpc19hdXRob3JpemVkX3RvX3VzZV91aSI6dHJ1ZSwiaXNDYW1wYWlnbkFwcHJvdmFsRW5hYmxlZCI6MCwiaWF0IjoxNzkxOTU5NzI1LCJleHAiOjE3OTIwMDI5MjV9.-MR658hCOa7Mtwx9KPShut-N1Y7C9omHUsUESfRB_BY\";',1792002920),('cdp-credix-cache-setting:credit_score_count_unpaid_overdue','b:1;',2107245416),('cdp-credix-cache-setting:credit_score_grace_days','i:0;',2107245416),('cdp-credix-cache-setting:credit_score_late_penalty_points','i:10;',2107245416),('cdp-credix-cache-setting:credit_score_on_time_points','i:10;',2107245416),('cdp-credix-cache-setting:loan_approval_segregation_enabled','b:1;',2107233043),('cdp-credix-cache-setting:max_loans_per_guarantor','i:1;',2107162640),('cdp-credix-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:6:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:10:\"group_name\";s:1:\"c\";s:4:\"name\";s:1:\"d\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";s:1:\"j\";s:12:\"is_protected\";}s:11:\"permissions\";a:205:{i:0;a:5:{s:1:\"a\";i:1;s:1:\"b\";s:31:\"Activity Management Permissions\";s:1:\"c\";s:14:\"Activity Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:1;a:5:{s:1:\"a\";i:2;s:1:\"b\";s:31:\"Activity Management Permissions\";s:1:\"c\";s:13:\"Activity Show\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:2;a:5:{s:1:\"a\";i:3;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:16:\"Permission Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:3;a:5:{s:1:\"a\";i:4;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:17:\"Permission Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:4;a:5:{s:1:\"a\";i:5;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:17:\"Permission Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:5;a:5:{s:1:\"a\";i:6;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:17:\"Permission Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:6;a:5:{s:1:\"a\";i:7;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:10:\"Role Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:7;a:5:{s:1:\"a\";i:8;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:11:\"Role Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:8;a:5:{s:1:\"a\";i:9;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:11:\"Role Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:9;a:5:{s:1:\"a\";i:10;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:11:\"Role Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:10;a:5:{s:1:\"a\";i:11;s:1:\"b\";s:27:\"User Management Permissions\";s:1:\"c\";s:10:\"User Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:11;a:5:{s:1:\"a\";i:12;s:1:\"b\";s:27:\"User Management Permissions\";s:1:\"c\";s:11:\"User Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:12;a:5:{s:1:\"a\";i:13;s:1:\"b\";s:27:\"User Management Permissions\";s:1:\"c\";s:11:\"User Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:13;a:5:{s:1:\"a\";i:14;s:1:\"b\";s:27:\"User Management Permissions\";s:1:\"c\";s:11:\"User Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:14;a:5:{s:1:\"a\";i:15;s:1:\"b\";s:27:\"User Management Permissions\";s:1:\"c\";s:18:\"User Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:15;a:5:{s:1:\"a\";i:16;s:1:\"b\";s:31:\"Employee Management Permissions\";s:1:\"c\";s:14:\"Employee Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:16;a:5:{s:1:\"a\";i:17;s:1:\"b\";s:30:\"Country Management Permissions\";s:1:\"c\";s:13:\"Country Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:17;a:5:{s:1:\"a\";i:18;s:1:\"b\";s:30:\"Country Management Permissions\";s:1:\"c\";s:14:\"Country Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:18;a:5:{s:1:\"a\";i:19;s:1:\"b\";s:30:\"Country Management Permissions\";s:1:\"c\";s:14:\"Country Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:19;a:5:{s:1:\"a\";i:20;s:1:\"b\";s:30:\"Country Management Permissions\";s:1:\"c\";s:14:\"Country Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:20;a:5:{s:1:\"a\";i:21;s:1:\"b\";s:30:\"Country Management Permissions\";s:1:\"c\";s:21:\"Country Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:21;a:5:{s:1:\"a\";i:22;s:1:\"b\";s:31:\"Province Management Permissions\";s:1:\"c\";s:14:\"Province Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:22;a:5:{s:1:\"a\";i:23;s:1:\"b\";s:31:\"Province Management Permissions\";s:1:\"c\";s:15:\"Province Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:23;a:5:{s:1:\"a\";i:24;s:1:\"b\";s:31:\"Province Management Permissions\";s:1:\"c\";s:15:\"Province Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:24;a:5:{s:1:\"a\";i:25;s:1:\"b\";s:31:\"Province Management Permissions\";s:1:\"c\";s:15:\"Province Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:25;a:5:{s:1:\"a\";i:26;s:1:\"b\";s:31:\"Province Management Permissions\";s:1:\"c\";s:22:\"Province Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:26;a:5:{s:1:\"a\";i:27;s:1:\"b\";s:28:\"Zonal Management Permissions\";s:1:\"c\";s:11:\"Zonal Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:27;a:5:{s:1:\"a\";i:28;s:1:\"b\";s:28:\"Zonal Management Permissions\";s:1:\"c\";s:12:\"Zonal Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:28;a:5:{s:1:\"a\";i:29;s:1:\"b\";s:28:\"Zonal Management Permissions\";s:1:\"c\";s:12:\"Zonal Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:29;a:5:{s:1:\"a\";i:30;s:1:\"b\";s:28:\"Zonal Management Permissions\";s:1:\"c\";s:12:\"Zonal Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:30;a:5:{s:1:\"a\";i:31;s:1:\"b\";s:28:\"Zonal Management Permissions\";s:1:\"c\";s:19:\"Zonal Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:31;a:5:{s:1:\"a\";i:32;s:1:\"b\";s:29:\"Region Management Permissions\";s:1:\"c\";s:12:\"Region Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:32;a:5:{s:1:\"a\";i:33;s:1:\"b\";s:29:\"Region Management Permissions\";s:1:\"c\";s:13:\"Region Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:33;a:5:{s:1:\"a\";i:34;s:1:\"b\";s:29:\"Region Management Permissions\";s:1:\"c\";s:13:\"Region Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:34;a:5:{s:1:\"a\";i:35;s:1:\"b\";s:29:\"Region Management Permissions\";s:1:\"c\";s:13:\"Region Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:35;a:5:{s:1:\"a\";i:36;s:1:\"b\";s:29:\"Region Management Permissions\";s:1:\"c\";s:20:\"Region Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:36;a:5:{s:1:\"a\";i:37;s:1:\"b\";s:29:\"Branch Management Permissions\";s:1:\"c\";s:12:\"Branch Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:37;a:5:{s:1:\"a\";i:38;s:1:\"b\";s:29:\"Branch Management Permissions\";s:1:\"c\";s:13:\"Branch Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:38;a:5:{s:1:\"a\";i:39;s:1:\"b\";s:29:\"Branch Management Permissions\";s:1:\"c\";s:13:\"Branch Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:39;a:5:{s:1:\"a\";i:40;s:1:\"b\";s:29:\"Branch Management Permissions\";s:1:\"c\";s:13:\"Branch Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:40;a:5:{s:1:\"a\";i:41;s:1:\"b\";s:29:\"Branch Management Permissions\";s:1:\"c\";s:20:\"Branch Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:41;a:5:{s:1:\"a\";i:42;s:1:\"b\";s:33:\"Department Management Permissions\";s:1:\"c\";s:16:\"Department Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:42;a:5:{s:1:\"a\";i:43;s:1:\"b\";s:33:\"Department Management Permissions\";s:1:\"c\";s:17:\"Department Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:43;a:5:{s:1:\"a\";i:44;s:1:\"b\";s:33:\"Department Management Permissions\";s:1:\"c\";s:17:\"Department Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:44;a:5:{s:1:\"a\";i:45;s:1:\"b\";s:33:\"Department Management Permissions\";s:1:\"c\";s:17:\"Department Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:45;a:5:{s:1:\"a\";i:46;s:1:\"b\";s:33:\"Department Management Permissions\";s:1:\"c\";s:24:\"Department Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:46;a:5:{s:1:\"a\";i:47;s:1:\"b\";s:34:\"Designation Management Permissions\";s:1:\"c\";s:17:\"Designation Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:47;a:5:{s:1:\"a\";i:48;s:1:\"b\";s:34:\"Designation Management Permissions\";s:1:\"c\";s:18:\"Designation Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:48;a:5:{s:1:\"a\";i:49;s:1:\"b\";s:34:\"Designation Management Permissions\";s:1:\"c\";s:18:\"Designation Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:49;a:5:{s:1:\"a\";i:50;s:1:\"b\";s:34:\"Designation Management Permissions\";s:1:\"c\";s:18:\"Designation Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:50;a:5:{s:1:\"a\";i:51;s:1:\"b\";s:34:\"Designation Management Permissions\";s:1:\"c\";s:25:\"Designation Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:51;a:5:{s:1:\"a\";i:52;s:1:\"b\";s:28:\"Group Management Permissions\";s:1:\"c\";s:11:\"Group Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:52;a:5:{s:1:\"a\";i:53;s:1:\"b\";s:28:\"Group Management Permissions\";s:1:\"c\";s:12:\"Group Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:53;a:5:{s:1:\"a\";i:54;s:1:\"b\";s:28:\"Group Management Permissions\";s:1:\"c\";s:12:\"Group Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:54;a:5:{s:1:\"a\";i:55;s:1:\"b\";s:28:\"Group Management Permissions\";s:1:\"c\";s:12:\"Group Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:55;a:5:{s:1:\"a\";i:56;s:1:\"b\";s:28:\"Group Management Permissions\";s:1:\"c\";s:19:\"Group Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:56;a:5:{s:1:\"a\";i:57;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:14:\"Customer Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:57;a:5:{s:1:\"a\";i:58;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:15:\"Customer Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:58;a:5:{s:1:\"a\";i:59;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:15:\"Customer Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:59;a:5:{s:1:\"a\";i:60;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:15:\"Customer Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:60;a:5:{s:1:\"a\";i:61;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:16:\"Customer Restore\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:61;a:5:{s:1:\"a\";i:62;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:21:\"Customer Force Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:62;a:5:{s:1:\"a\";i:63;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:22:\"Customer Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:63;a:5:{s:1:\"a\";i:64;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:38:\"Customer Financials Live Loan Override\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:64;a:5:{s:1:\"a\";i:65;s:1:\"b\";s:43:\"Customer Bank Detail Management Permissions\";s:1:\"c\";s:26:\"Customer Bank Detail Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:65;a:5:{s:1:\"a\";i:66;s:1:\"b\";s:43:\"Customer Bank Detail Management Permissions\";s:1:\"c\";s:27:\"Customer Bank Detail Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:66;a:5:{s:1:\"a\";i:67;s:1:\"b\";s:43:\"Customer Bank Detail Management Permissions\";s:1:\"c\";s:27:\"Customer Bank Detail Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:67;a:5:{s:1:\"a\";i:68;s:1:\"b\";s:43:\"Customer Bank Detail Management Permissions\";s:1:\"c\";s:27:\"Customer Bank Detail Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:68;a:5:{s:1:\"a\";i:69;s:1:\"b\";s:43:\"Customer Bank Detail Management Permissions\";s:1:\"c\";s:34:\"Customer Bank Detail Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:69;a:5:{s:1:\"a\";i:70;s:1:\"b\";s:32:\"Guarantor Management Permissions\";s:1:\"c\";s:15:\"Guarantor Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:70;a:5:{s:1:\"a\";i:71;s:1:\"b\";s:32:\"Guarantor Management Permissions\";s:1:\"c\";s:16:\"Guarantor Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:71;a:5:{s:1:\"a\";i:72;s:1:\"b\";s:32:\"Guarantor Management Permissions\";s:1:\"c\";s:16:\"Guarantor Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:72;a:5:{s:1:\"a\";i:73;s:1:\"b\";s:32:\"Guarantor Management Permissions\";s:1:\"c\";s:16:\"Guarantor Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:73;a:5:{s:1:\"a\";i:74;s:1:\"b\";s:34:\"Application Management Permissions\";s:1:\"c\";s:17:\"Application Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:74;a:5:{s:1:\"a\";i:75;s:1:\"b\";s:34:\"Application Management Permissions\";s:1:\"c\";s:18:\"Application Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:75;a:5:{s:1:\"a\";i:76;s:1:\"b\";s:34:\"Application Management Permissions\";s:1:\"c\";s:18:\"Application Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:76;a:5:{s:1:\"a\";i:77;s:1:\"b\";s:34:\"Application Management Permissions\";s:1:\"c\";s:18:\"Application Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:77;a:5:{s:1:\"a\";i:78;s:1:\"b\";s:34:\"Fixed Asset Management Permissions\";s:1:\"c\";s:17:\"Fixed Asset Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:78;a:5:{s:1:\"a\";i:79;s:1:\"b\";s:34:\"Fixed Asset Management Permissions\";s:1:\"c\";s:18:\"Fixed Asset Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:79;a:5:{s:1:\"a\";i:80;s:1:\"b\";s:34:\"Fixed Asset Management Permissions\";s:1:\"c\";s:18:\"Fixed Asset Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:80;a:5:{s:1:\"a\";i:81;s:1:\"b\";s:34:\"Fixed Asset Management Permissions\";s:1:\"c\";s:18:\"Fixed Asset Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:81;a:5:{s:1:\"a\";i:82;s:1:\"b\";s:35:\"Moving Asset Management Permissions\";s:1:\"c\";s:18:\"Moving Asset Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:82;a:5:{s:1:\"a\";i:83;s:1:\"b\";s:35:\"Moving Asset Management Permissions\";s:1:\"c\";s:19:\"Moving Asset Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:83;a:5:{s:1:\"a\";i:84;s:1:\"b\";s:35:\"Moving Asset Management Permissions\";s:1:\"c\";s:19:\"Moving Asset Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:84;a:5:{s:1:\"a\";i:85;s:1:\"b\";s:35:\"Moving Asset Management Permissions\";s:1:\"c\";s:19:\"Moving Asset Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:85;a:5:{s:1:\"a\";i:86;s:1:\"b\";s:32:\"Liability Management Permissions\";s:1:\"c\";s:15:\"Liability Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:86;a:5:{s:1:\"a\";i:87;s:1:\"b\";s:32:\"Liability Management Permissions\";s:1:\"c\";s:16:\"Liability Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:87;a:5:{s:1:\"a\";i:88;s:1:\"b\";s:32:\"Liability Management Permissions\";s:1:\"c\";s:16:\"Liability Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:88;a:5:{s:1:\"a\";i:89;s:1:\"b\";s:32:\"Liability Management Permissions\";s:1:\"c\";s:16:\"Liability Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:89;a:5:{s:1:\"a\";i:90;s:1:\"b\";s:32:\"Liability Management Permissions\";s:1:\"c\";s:23:\"Liability Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:90;a:5:{s:1:\"a\";i:91;s:1:\"b\";s:31:\"Document Management Permissions\";s:1:\"c\";s:14:\"Document Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:91;a:5:{s:1:\"a\";i:92;s:1:\"b\";s:31:\"Document Management Permissions\";s:1:\"c\";s:15:\"Document Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:92;a:5:{s:1:\"a\";i:93;s:1:\"b\";s:31:\"Document Management Permissions\";s:1:\"c\";s:15:\"Document Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:93;a:5:{s:1:\"a\";i:94;s:1:\"b\";s:31:\"Document Management Permissions\";s:1:\"c\";s:15:\"Document Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:94;a:5:{s:1:\"a\";i:95;s:1:\"b\";s:32:\"Loan Term Management Permissions\";s:1:\"c\";s:15:\"Loan Term Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:95;a:5:{s:1:\"a\";i:96;s:1:\"b\";s:32:\"Loan Term Management Permissions\";s:1:\"c\";s:16:\"Loan Term Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:96;a:5:{s:1:\"a\";i:97;s:1:\"b\";s:32:\"Loan Term Management Permissions\";s:1:\"c\";s:16:\"Loan Term Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:97;a:5:{s:1:\"a\";i:98;s:1:\"b\";s:32:\"Loan Term Management Permissions\";s:1:\"c\";s:16:\"Loan Term Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:98;a:5:{s:1:\"a\";i:99;s:1:\"b\";s:32:\"Loan Term Management Permissions\";s:1:\"c\";s:23:\"Loan Term Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:99;a:5:{s:1:\"a\";i:100;s:1:\"b\";s:32:\"Loan Type Management Permissions\";s:1:\"c\";s:15:\"Loan Type Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:100;a:5:{s:1:\"a\";i:101;s:1:\"b\";s:32:\"Loan Type Management Permissions\";s:1:\"c\";s:16:\"Loan Type Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:101;a:5:{s:1:\"a\";i:102;s:1:\"b\";s:32:\"Loan Type Management Permissions\";s:1:\"c\";s:16:\"Loan Type Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:102;a:5:{s:1:\"a\";i:103;s:1:\"b\";s:32:\"Loan Type Management Permissions\";s:1:\"c\";s:16:\"Loan Type Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:103;a:5:{s:1:\"a\";i:104;s:1:\"b\";s:32:\"Loan Type Management Permissions\";s:1:\"c\";s:23:\"Loan Type Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:104;a:5:{s:1:\"a\";i:105;s:1:\"b\";s:35:\"Loan Product Management Permissions\";s:1:\"c\";s:18:\"Loan Product Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:105;a:5:{s:1:\"a\";i:106;s:1:\"b\";s:35:\"Loan Product Management Permissions\";s:1:\"c\";s:19:\"Loan Product Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:106;a:5:{s:1:\"a\";i:107;s:1:\"b\";s:35:\"Loan Product Management Permissions\";s:1:\"c\";s:19:\"Loan Product Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:107;a:5:{s:1:\"a\";i:108;s:1:\"b\";s:35:\"Loan Product Management Permissions\";s:1:\"c\";s:19:\"Loan Product Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:108;a:5:{s:1:\"a\";i:109;s:1:\"b\";s:35:\"Loan Product Management Permissions\";s:1:\"c\";s:26:\"Loan Product Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:109;a:5:{s:1:\"a\";i:110;s:1:\"b\";s:25:\"Global Search Permissions\";s:1:\"c\";s:13:\"Global Search\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:110;a:5:{s:1:\"a\";i:111;s:1:\"b\";s:23:\"CDP Connect Permissions\";s:1:\"c\";s:25:\"CDP Customer Verification\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:111;a:5:{s:1:\"a\";i:112;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:22:\"Loan Application Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:112;a:5:{s:1:\"a\";i:113;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:113;a:5:{s:1:\"a\";i:114;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:114;a:5:{s:1:\"a\";i:115;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:115;a:5:{s:1:\"a\";i:116;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:30:\"Loan Application Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:116;a:5:{s:1:\"a\";i:117;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Review\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:117;a:5:{s:1:\"a\";i:118;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:25:\"Loan Application Resubmit\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:118;a:5:{s:1:\"a\";i:119;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Verify\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:119;a:5:{s:1:\"a\";i:120;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:25:\"Loan Application Reverify\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:120;a:5:{s:1:\"a\";i:121;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:24:\"Loan Application Approve\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:121;a:5:{s:1:\"a\";i:122;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Reject\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:122;a:5:{s:1:\"a\";i:123;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Reopen\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:123;a:5:{s:1:\"a\";i:124;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:31:\"Loan Application Offer Response\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:124;a:5:{s:1:\"a\";i:125;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:37:\"Loan Application Status History Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:125;a:5:{s:1:\"a\";i:126;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:20:\"Legal Template Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:126;a:5:{s:1:\"a\";i:127;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:21:\"Legal Template Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:127;a:5:{s:1:\"a\";i:128;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:21:\"Legal Template Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:128;a:5:{s:1:\"a\";i:129;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:21:\"Legal Template Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:129;a:5:{s:1:\"a\";i:130;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:28:\"Legal Template Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:130;a:5:{s:1:\"a\";i:131;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:20:\"Legal Document Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:131;a:5:{s:1:\"a\";i:132;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:21:\"Legal Document Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:132;a:5:{s:1:\"a\";i:133;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:21:\"Legal Document Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:133;a:5:{s:1:\"a\";i:134;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:21:\"Legal Document Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:134;a:5:{s:1:\"a\";i:135;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:28:\"Legal Document Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:135;a:5:{s:1:\"a\";i:136;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:25:\"Loan Application Disburse\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:136;a:5:{s:1:\"a\";i:137;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Cancel\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:137;a:5:{s:1:\"a\";i:138;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:16:\"Group Loan Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:138;a:5:{s:1:\"a\";i:139;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:139;a:5:{s:1:\"a\";i:140;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:140;a:5:{s:1:\"a\";i:141;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:141;a:5:{s:1:\"a\";i:142;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:24:\"Group Loan Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:142;a:5:{s:1:\"a\";i:143;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Review\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:143;a:5:{s:1:\"a\";i:144;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:19:\"Group Loan Resubmit\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:144;a:5:{s:1:\"a\";i:145;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Verify\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:145;a:5:{s:1:\"a\";i:146;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:19:\"Group Loan Reverify\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:146;a:5:{s:1:\"a\";i:147;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:18:\"Group Loan Approve\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:147;a:5:{s:1:\"a\";i:148;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Reject\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:148;a:5:{s:1:\"a\";i:149;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Reopen\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:149;a:5:{s:1:\"a\";i:150;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:25:\"Group Loan Offer Response\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:150;a:5:{s:1:\"a\";i:151;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:19:\"Group Loan Disburse\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:151;a:5:{s:1:\"a\";i:152;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Cancel\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:152;a:5:{s:1:\"a\";i:153;s:1:\"b\";s:38:\"Group Loan Item Management Permissions\";s:1:\"c\";s:21:\"Group Loan Item Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:153;a:5:{s:1:\"a\";i:154;s:1:\"b\";s:38:\"Group Loan Item Management Permissions\";s:1:\"c\";s:22:\"Group Loan Item Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:154;a:5:{s:1:\"a\";i:155;s:1:\"b\";s:38:\"Group Loan Item Management Permissions\";s:1:\"c\";s:22:\"Group Loan Item Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:155;a:5:{s:1:\"a\";i:156;s:1:\"b\";s:36:\"Loan Revision Management Permissions\";s:1:\"c\";s:19:\"Loan Revision Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:156;a:5:{s:1:\"a\";i:157;s:1:\"b\";s:36:\"Loan Revision Management Permissions\";s:1:\"c\";s:20:\"Loan Revision Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:157;a:5:{s:1:\"a\";i:158;s:1:\"b\";s:36:\"Loan Revision Management Permissions\";s:1:\"c\";s:21:\"Loan Revision Approve\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:158;a:5:{s:1:\"a\";i:159;s:1:\"b\";s:36:\"Loan Revision Management Permissions\";s:1:\"c\";s:20:\"Loan Revision Reject\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:159;a:5:{s:1:\"a\";i:160;s:1:\"b\";s:36:\"Loan Revision Management Permissions\";s:1:\"c\";s:20:\"Loan Revision Cancel\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:160;a:5:{s:1:\"a\";i:161;s:1:\"b\";s:49:\"Loan Application Guarantor Management Permissions\";s:1:\"c\";s:32:\"Loan Application Guarantor Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:161;a:5:{s:1:\"a\";i:162;s:1:\"b\";s:49:\"Loan Application Guarantor Management Permissions\";s:1:\"c\";s:33:\"Loan Application Guarantor Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:162;a:5:{s:1:\"a\";i:163;s:1:\"b\";s:49:\"Loan Application Guarantor Management Permissions\";s:1:\"c\";s:33:\"Loan Application Guarantor Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:163;a:5:{s:1:\"a\";i:164;s:1:\"b\";s:49:\"Loan Application Guarantor Management Permissions\";s:1:\"c\";s:33:\"Loan Application Guarantor Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:164;a:5:{s:1:\"a\";i:165;s:1:\"b\";s:48:\"Loan Application Customer Management Permissions\";s:1:\"c\";s:31:\"Loan Application Customer Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:165;a:5:{s:1:\"a\";i:166;s:1:\"b\";s:48:\"Loan Application Customer Management Permissions\";s:1:\"c\";s:32:\"Loan Application Customer Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:166;a:5:{s:1:\"a\";i:167;s:1:\"b\";s:48:\"Loan Application Customer Management Permissions\";s:1:\"c\";s:32:\"Loan Application Customer Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:167;a:5:{s:1:\"a\";i:168;s:1:\"b\";s:48:\"Loan Application Customer Management Permissions\";s:1:\"c\";s:32:\"Loan Application Customer Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:168;a:5:{s:1:\"a\";i:169;s:1:\"b\";s:39:\"Loan Installment Management Permissions\";s:1:\"c\";s:22:\"Loan Installment Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:169;a:5:{s:1:\"a\";i:170;s:1:\"b\";s:39:\"Loan Installment Management Permissions\";s:1:\"c\";s:23:\"Loan Installment Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:170;a:5:{s:1:\"a\";i:171;s:1:\"b\";s:39:\"Loan Installment Management Permissions\";s:1:\"c\";s:23:\"Loan Installment Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:171;a:5:{s:1:\"a\";i:172;s:1:\"b\";s:39:\"Loan Installment Management Permissions\";s:1:\"c\";s:23:\"Loan Installment Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:172;a:5:{s:1:\"a\";i:173;s:1:\"b\";s:30:\"Payment Management Permissions\";s:1:\"c\";s:13:\"Payment Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:173;a:5:{s:1:\"a\";i:174;s:1:\"b\";s:30:\"Payment Management Permissions\";s:1:\"c\";s:14:\"Payment Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:174;a:5:{s:1:\"a\";i:175;s:1:\"b\";s:30:\"Payment Management Permissions\";s:1:\"c\";s:14:\"Payment Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:175;a:5:{s:1:\"a\";i:176;s:1:\"b\";s:30:\"Payment Management Permissions\";s:1:\"c\";s:14:\"Payment Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:176;a:5:{s:1:\"a\";i:177;s:1:\"b\";s:36:\"Recovery Case Management Permissions\";s:1:\"c\";s:19:\"Recovery Case Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:177;a:5:{s:1:\"a\";i:178;s:1:\"b\";s:36:\"Recovery Case Management Permissions\";s:1:\"c\";s:20:\"Recovery Case Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:178;a:5:{s:1:\"a\";i:179;s:1:\"b\";s:36:\"Recovery Case Management Permissions\";s:1:\"c\";s:20:\"Recovery Case Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:179;a:5:{s:1:\"a\";i:180;s:1:\"b\";s:36:\"Recovery Case Management Permissions\";s:1:\"c\";s:20:\"Recovery Case Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:180;a:5:{s:1:\"a\";i:181;s:1:\"b\";s:40:\"Recovery Activity Management Permissions\";s:1:\"c\";s:23:\"Recovery Activity Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:181;a:5:{s:1:\"a\";i:182;s:1:\"b\";s:40:\"Recovery Activity Management Permissions\";s:1:\"c\";s:24:\"Recovery Activity Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:182;a:5:{s:1:\"a\";i:183;s:1:\"b\";s:40:\"Recovery Activity Management Permissions\";s:1:\"c\";s:24:\"Recovery Activity Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:183;a:5:{s:1:\"a\";i:184;s:1:\"b\";s:40:\"Recovery Activity Management Permissions\";s:1:\"c\";s:24:\"Recovery Activity Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:184;a:5:{s:1:\"a\";i:185;s:1:\"b\";s:37:\"Recovery Agent Management Permissions\";s:1:\"c\";s:20:\"Recovery Agent Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:185;a:5:{s:1:\"a\";i:186;s:1:\"b\";s:37:\"Recovery Agent Management Permissions\";s:1:\"c\";s:21:\"Recovery Agent Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:186;a:5:{s:1:\"a\";i:187;s:1:\"b\";s:37:\"Recovery Agent Management Permissions\";s:1:\"c\";s:21:\"Recovery Agent Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:187;a:5:{s:1:\"a\";i:188;s:1:\"b\";s:37:\"Recovery Agent Management Permissions\";s:1:\"c\";s:21:\"Recovery Agent Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:188;a:5:{s:1:\"a\";i:189;s:1:\"b\";s:46:\"External Recovery Agent Management Permissions\";s:1:\"c\";s:29:\"External Recovery Agent Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:189;a:5:{s:1:\"a\";i:190;s:1:\"b\";s:46:\"External Recovery Agent Management Permissions\";s:1:\"c\";s:30:\"External Recovery Agent Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:190;a:5:{s:1:\"a\";i:191;s:1:\"b\";s:46:\"External Recovery Agent Management Permissions\";s:1:\"c\";s:30:\"External Recovery Agent Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:191;a:5:{s:1:\"a\";i:192;s:1:\"b\";s:46:\"External Recovery Agent Management Permissions\";s:1:\"c\";s:30:\"External Recovery Agent Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:192;a:5:{s:1:\"a\";i:193;s:1:\"b\";s:35:\"Notification Management Permissions\";s:1:\"c\";s:18:\"Notification Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:193;a:5:{s:1:\"a\";i:194;s:1:\"b\";s:27:\"System Settings Permissions\";s:1:\"c\";s:13:\"Setting Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:194;a:5:{s:1:\"a\";i:195;s:1:\"b\";s:27:\"System Settings Permissions\";s:1:\"c\";s:14:\"Setting Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:195;a:5:{s:1:\"a\";i:196;s:1:\"b\";s:25:\"Loan Security Permissions\";s:1:\"c\";s:10:\"use_plan_1\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:196;a:5:{s:1:\"a\";i:197;s:1:\"b\";s:25:\"Loan Security Permissions\";s:1:\"c\";s:10:\"use_plan_2\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:197;a:5:{s:1:\"a\";i:198;s:1:\"b\";s:25:\"Loan Security Permissions\";s:1:\"c\";s:10:\"use_plan_3\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:198;a:5:{s:1:\"a\";i:199;s:1:\"b\";s:25:\"Loan Security Permissions\";s:1:\"c\";s:15:\"use_cdp_inv_001\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:199;a:5:{s:1:\"a\";i:200;s:1:\"b\";s:25:\"Loan Security Permissions\";s:1:\"c\";s:15:\"use_cdp_pro_001\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:200;a:5:{s:1:\"a\";i:201;s:1:\"b\";s:25:\"Loan Security Permissions\";s:1:\"c\";s:15:\"use_cdp_vec_001\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:201;a:5:{s:1:\"a\";i:202;s:1:\"b\";s:35:\"Credit Score Management Permissions\";s:1:\"c\";s:18:\"Credit Score Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:202;a:5:{s:1:\"a\";i:203;s:1:\"b\";s:35:\"Credit Score Management Permissions\";s:1:\"c\";s:22:\"Credit Score Recompute\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:203;a:5:{s:1:\"a\";i:204;s:1:\"b\";s:38:\"Admin Dashboard Management Permissions\";s:1:\"c\";s:21:\"Admin Dashboard Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:204;a:5:{s:1:\"a\";i:205;s:1:\"b\";s:29:\"Report Management Permissions\";s:1:\"c\";s:12:\"Report Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}}s:5:\"roles\";a:2:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"c\";s:11:\"Super Admin\";s:1:\"d\";s:3:\"api\";s:1:\"j\";i:0;}i:1;a:4:{s:1:\"a\";i:2;s:1:\"c\";s:8:\"Employee\";s:1:\"d\";s:3:\"api\";s:1:\"j\";i:0;}}}',1792039091);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (1,'Srilanka','LK',NULL,1,'2026-10-12 10:48:53','2026-10-12 10:48:53');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_score_events`
--

DROP TABLE IF EXISTS `credit_score_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_score_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `loan_application_id` bigint unsigned NOT NULL,
  `loan_installment_id` bigint unsigned NOT NULL,
  `event_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `points` decimal(8,2) NOT NULL,
  `days_late` int unsigned NOT NULL DEFAULT '0',
  `occurred_on` date DEFAULT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_score_events_installment_unique` (`loan_installment_id`),
  KEY `credit_score_events_loan_application_id_foreign` (`loan_application_id`),
  KEY `credit_score_events_customer_loan_index` (`customer_id`,`loan_application_id`),
  KEY `credit_score_events_event_type_index` (`event_type`),
  CONSTRAINT `credit_score_events_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_score_events_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_score_events_loan_installment_id_foreign` FOREIGN KEY (`loan_installment_id`) REFERENCES `loan_installments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_score_events`
--

LOCK TABLES `credit_score_events` WRITE;
/*!40000 ALTER TABLE `credit_score_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_score_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_bank_details`
--

DROP TABLE IF EXISTS `customer_bank_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_bank_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bank_transfer',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_bank_details_customer_id_foreign` (`customer_id`),
  CONSTRAINT `customer_bank_details_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_bank_details`
--

LOCK TABLES `customer_bank_details` WRITE;
/*!40000 ALTER TABLE `customer_bank_details` DISABLE KEYS */;
INSERT INTO `customer_bank_details` VALUES (1,2,'HNB','Colombo','1112223456','Bank Transfer',1,NULL,'2026-10-13 03:37:48','2026-10-13 03:37:48'),(2,3,'NTB','Colombo','1234567890','Bank Transfer',1,NULL,'2026-10-13 03:41:08','2026-10-13 03:41:08'),(3,4,'HNB','Colombo','1234567899','Bank Transfer',1,NULL,'2026-10-13 05:10:22','2026-10-13 05:10:22');
/*!40000 ALTER TABLE `customer_bank_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_credit_scores`
--

DROP TABLE IF EXISTS `customer_credit_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_credit_scores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `loan_application_id` bigint unsigned NOT NULL,
  `installments_counted` int unsigned NOT NULL DEFAULT '0',
  `on_time_count` int unsigned NOT NULL DEFAULT '0',
  `late_count` int unsigned NOT NULL DEFAULT '0',
  `final_score` decimal(8,2) DEFAULT NULL,
  `computed_at` timestamp NULL DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_credit_scores_loan_customer_unique` (`loan_application_id`,`customer_id`),
  KEY `customer_credit_scores_customer_index` (`customer_id`),
  CONSTRAINT `customer_credit_scores_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_credit_scores_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_credit_scores`
--

LOCK TABLES `customer_credit_scores` WRITE;
/*!40000 ALTER TABLE `customer_credit_scores` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_credit_scores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_details`
--

DROP TABLE IF EXISTS `customer_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `gn_division` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ds_division` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_details_customer_id_unique` (`customer_id`),
  CONSTRAINT `customer_details_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_details`
--

LOCK TABLES `customer_details` WRITE;
/*!40000 ALTER TABLE `customer_details` DISABLE KEYS */;
INSERT INTO `customer_details` VALUES (1,1,'Colombo','Colombo','colombo','Western','2026-10-12 10:54:53','2026-10-12 10:54:53'),(2,2,'Colombo','Colombo','Colombo','Western','2026-10-13 03:37:48','2026-10-13 03:37:48'),(3,3,'Colombo','Colombo','Colombo','western','2026-10-13 03:41:08','2026-10-13 03:41:08'),(4,4,'Colombo','Colombo','Colombo','Western','2026-10-13 05:10:22','2026-10-13 05:10:22'),(5,5,'Colombo','Colombo','Colombo','Western','2026-10-13 06:36:56','2026-10-13 06:36:56'),(6,6,'Colombo','Colombo','Colombo','Colombo','2026-10-14 07:38:56','2026-10-14 07:38:56');
/*!40000 ALTER TABLE `customer_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_verifications`
--

DROP TABLE IF EXISTS `customer_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `snapshot_version` int unsigned NOT NULL DEFAULT '1',
  `snapshot_data` json NOT NULL,
  `otp_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_verifications_created_by_foreign` (`created_by`),
  KEY `customer_verifications_verified_by_foreign` (`verified_by`),
  KEY `customer_verifications_customer_id_status_index` (`customer_id`,`status`),
  KEY `customer_verifications_expires_at_index` (`expires_at`),
  CONSTRAINT `customer_verifications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_verifications_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_verifications_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_verifications`
--

LOCK TABLES `customer_verifications` WRITE;
/*!40000 ALTER TABLE `customer_verifications` DISABLE KEYS */;
INSERT INTO `customer_verifications` VALUES (1,1,1,'0752932640',1,'{\"customer\": {\"id\": 1, \"email\": \"piranya105@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Piranya\", \"id_number\": \"200012345678\", \"employment\": {\"status\": null, \"occupation\": null, \"employer_name\": null, \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0001\", \"customer_code\": \"CUS0001\", \"date_of_birth\": \"2026-10-03\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.Piranya\", \"preferred_language\": \"Tamil\"}, \"captured_at\": \"2026-10-12T16:25:10+05:30\"}','220310d691ea96f65eaef864ec3f00dae7deaa7c78aed95033fcd006d7f487a8','verified',0,'2026-10-12 16:55:10','2026-10-12 16:25:35',1,'2026-10-12 10:55:10','2026-10-12 10:55:35'),(2,2,1,'0752932640',1,'{\"customer\": {\"id\": 2, \"email\": \"piranyacdp@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Nilukshi\", \"id_number\": \"199712345678\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0002\", \"customer_code\": \"CUS0002\", \"date_of_birth\": \"2026-10-02\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.Nilukshi\", \"preferred_language\": \"Sinhala\"}, \"captured_at\": \"2026-10-13T09:11:26+05:30\"}','d8b7c96220073b9b1541394ec63ec46046aa08617056ba2cf387f646310f8096','verified',0,'2026-10-13 09:41:26','2026-10-13 09:11:46',1,'2026-10-13 03:41:26','2026-10-13 03:41:46'),(3,3,1,'0752932640',1,'{\"customer\": {\"id\": 3, \"email\": null, \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Karuna\", \"id_number\": \"199512345678\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0003\", \"customer_code\": \"CUS0003\", \"date_of_birth\": \"2026-10-03\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"K.Karuna\", \"preferred_language\": \"Sinhala\"}, \"captured_at\": \"2026-10-13T09:12:04+05:30\"}','2cbd75189f20071db0ccff657295233eddce55441e71e43ed91a84dddfc9ba7f','verified',0,'2026-10-13 09:42:04','2026-10-13 09:12:18',1,'2026-10-13 03:42:04','2026-10-13 03:42:18'),(4,4,1,'0752932640',1,'{\"customer\": {\"id\": 4, \"email\": \"cdppiranya@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Mithurjan\", \"id_number\": \"200312345678\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0004\", \"customer_code\": \"CUS0004\", \"date_of_birth\": \"2026-10-01\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.MIthurjan\", \"preferred_language\": \"Sinhala\"}, \"captured_at\": \"2026-10-13T10:42:13+05:30\"}','4c0034188965512ecacaa855ed75b5c27626987be2bd07192ef33b982b5c24c2','verified',0,'2026-10-13 11:12:13','2026-10-13 10:43:10',1,'2026-10-13 05:12:13','2026-10-13 05:13:10'),(5,4,1,'0752932640',2,'{\"customer\": {\"id\": 4, \"email\": \"cdppiranya@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Mithurjan\", \"id_number\": \"200312345678\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0004\", \"customer_code\": \"CUS0004\", \"date_of_birth\": \"2026-10-01\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.MIthurjan\", \"preferred_language\": \"Sinhala\"}, \"captured_at\": \"2026-10-14T12:05:18+05:30\"}','af7ba978d99db3c8f9967c60630d85b6e63ae31afd891e67e313dca7f0e2a27d','verified',0,'2026-10-14 12:35:18','2026-10-14 12:05:37',1,'2026-10-14 06:35:18','2026-10-14 06:35:37'),(6,6,1,'0752932640',1,'{\"customer\": {\"id\": 6, \"email\": null, \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Karinath\", \"id_number\": \"200012345679\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0006\", \"customer_code\": \"CUS0006\", \"date_of_birth\": \"2026-10-01\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Colombo\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"K.Karinath\", \"preferred_language\": \"Sinhala\"}, \"captured_at\": \"2026-10-14T13:09:09+05:30\"}','88172e930e9127d6d166db31727ecb9bef3393e376b0903a3289aa4a51811b81','verified',0,'2026-10-14 13:39:09','2026-10-14 13:09:26',1,'2026-10-14 07:39:09','2026-10-14 07:39:26');
/*!40000 ALTER TABLE `customer_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_with_initials` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address_line_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `landmark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_primary` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_secondary` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `have_whatsapp` tinyint(1) NOT NULL DEFAULT '0',
  `whatsapp_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_language` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monthly_income` decimal(15,2) DEFAULT NULL,
  `business_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_registration_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_nature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `applicant_role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_application_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `fixed_allowances` decimal(15,2) DEFAULT NULL,
  `other_allowances` decimal(15,2) DEFAULT NULL,
  `other_income` decimal(15,2) DEFAULT NULL,
  `total_monthly_income` decimal(15,2) DEFAULT NULL,
  `other_expenses` decimal(15,2) DEFAULT NULL,
  `total_monthly_expenses` decimal(15,2) DEFAULT NULL,
  `recommended_by_employee_id` bigint unsigned DEFAULT NULL,
  `recommender_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recommender_employee_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recommender_nic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recommender_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_score` decimal(8,2) DEFAULT NULL,
  `credit_score_on_time_rate` decimal(5,2) DEFAULT NULL,
  `credit_score_updated_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_kyc_verified` tinyint(1) NOT NULL DEFAULT '0',
  `kyc_verified_at` datetime DEFAULT NULL,
  `current_kyc_verification_id` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_customer_id_unique` (`customer_id`),
  UNIQUE KEY `customers_customer_code_unique` (`customer_code`),
  UNIQUE KEY `customers_id_number_unique` (`id_number`),
  KEY `customers_current_application_id_foreign` (`current_application_id`),
  KEY `customers_branch_id_foreign` (`branch_id`),
  KEY `customers_recommended_by_employee_id_index` (`recommended_by_employee_id`),
  KEY `customers_credit_score_index` (`credit_score`),
  KEY `customers_current_kyc_verification_id_foreign` (`current_kyc_verification_id`),
  CONSTRAINT `customers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_current_application_id_foreign` FOREIGN KEY (`current_application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_current_kyc_verification_id_foreign` FOREIGN KEY (`current_kyc_verification_id`) REFERENCES `customer_verifications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_recommended_by_employee_id_foreign` FOREIGN KEY (`recommended_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'CUST-0001','CUS0001','Piranya','P.Piranya','NIC','200012345678','2026-10-03','12, Main rd',NULL,NULL,'Colombo',NULL,'Sri Lanka',NULL,'0752932640',NULL,'piranya105@gmail.com',0,NULL,'Tamil',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2026-10-12 16:25:35',1,NULL,'2026-10-12 10:54:53','2026-10-12 10:55:35'),(2,'CUST-0002','CUS0002','Nilukshi','P.Nilukshi','NIC','199712345678','2026-10-02','12, Main rd',NULL,NULL,'Colombo',NULL,'Sri Lanka',NULL,'0752932640',NULL,'piranyacdp@gmail.com',0,NULL,'Sinhala','Employed','SE','CDP',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2026-10-13 09:11:46',2,NULL,'2026-10-13 03:37:48','2026-10-13 03:41:46'),(3,'CUST-0003','CUS0003','Karuna','K.Karuna','NIC','199512345678','2026-10-03','12, Main rd',NULL,NULL,'Colombo',NULL,'Sri Lanka',NULL,'0752932640',NULL,NULL,0,NULL,'Sinhala','Employed','SE','CDP',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2026-10-13 09:12:18',3,NULL,'2026-10-13 03:41:08','2026-10-13 03:42:18'),(4,'CUST-0004','CUS0004','Mithurjan','P.MIthurjan','NIC','200312345678','2026-10-01','12, Main rd',NULL,NULL,'Colombo',NULL,'Sri Lanka',NULL,'0752932640',NULL,'cdppiranya@gmail.com',0,NULL,'Sinhala','Employed','SE','CDP',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2026-10-14 12:05:37',5,NULL,'2026-10-13 05:10:22','2026-10-14 06:35:37'),(5,'CUST-0005','CUS0005','Mithu','N.Mithu','NIC','200212345678','2026-10-01','12, Main rd',NULL,NULL,'Colombo',NULL,'Sri Lanka',NULL,'0752932640',NULL,NULL,0,NULL,'Sinhala','Employed','SE','CDP',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,'2026-10-13 06:36:56','2026-10-13 06:36:56'),(6,'CUST-0006','CUS0006','Karinath','K.Karinath','NIC','200012345679','2026-10-01','12, Main rd',NULL,NULL,'Colombo',NULL,'Sri Lanka',NULL,'0752932640',NULL,NULL,0,NULL,'Sinhala','Employed','SE','CDP',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2026-10-14 13:09:26',6,NULL,'2026-10-14 07:38:56','2026-10-14 07:39:26');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dashboard_targets`
--

DROP TABLE IF EXISTS `dashboard_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_targets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `metric` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_value` decimal(15,2) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dashboard_targets_branch_id_foreign` (`branch_id`),
  KEY `dashboard_targets_created_by_foreign` (`created_by`),
  KEY `dashboard_targets_metric_period_idx` (`metric`,`period_start`,`period_end`),
  KEY `dashboard_targets_metric_index` (`metric`),
  CONSTRAINT `dashboard_targets_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dashboard_targets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dashboard_targets`
--

LOCK TABLES `dashboard_targets` WRITE;
/*!40000 ALTER TABLE `dashboard_targets` DISABLE KEYS */;
/*!40000 ALTER TABLE `dashboard_targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `head_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_code_unique` (`code`),
  UNIQUE KEY `departments_head_id_unique` (`head_id`),
  CONSTRAINT `departments_head_id_foreign` FOREIGN KEY (`head_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `designations`
--

DROP TABLE IF EXISTS `designations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `designations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_weight` int NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `designations_code_unique` (`code`),
  KEY `designations_department_id_foreign` (`department_id`),
  CONSTRAINT `designations_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `designations`
--

LOCK TABLES `designations` WRITE;
/*!40000 ALTER TABLE `designations` DISABLE KEYS */;
/*!40000 ALTER TABLE `designations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `document_type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT '0',
  `document_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `loan_application_id` bigint unsigned DEFAULT NULL,
  `guarantor_id` bigint unsigned DEFAULT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','rejected','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_customer_id_foreign` (`customer_id`),
  KEY `documents_guarantor_id_foreign` (`guarantor_id`),
  KEY `documents_uploaded_by_foreign` (`uploaded_by`),
  KEY `documents_document_type_index` (`document_type`),
  KEY `documents_loan_application_id_index` (`loan_application_id`),
  CONSTRAINT `documents_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `documents_guarantor_id_foreign` FOREIGN KEY (`guarantor_id`) REFERENCES `guarantors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES (1,'nic_copy',0,'WhatsApp Image 2026-10-09 at 9.06.59 AM','uploads/documents/2_WhatsApp Image 2026-10-09 at 9.06.59 AM.jpeg',2,NULL,NULL,NULL,'2026-10-13 03:37:48','active',NULL,1,NULL,'2026-10-13 03:37:48','2026-10-13 03:37:48'),(2,'salary_slip',0,'WhatsApp Image 2026-10-06 at 12.10.04 PM','uploads/documents/2_WhatsApp Image 2026-10-06 at 12.10.04 PM.jpeg',2,NULL,NULL,NULL,'2026-10-13 03:37:48','active',NULL,1,NULL,'2026-10-13 03:37:48','2026-10-13 03:37:48'),(3,'salary_slip',0,'WhatsApp Image 2026-07-30 at 3.15.36 PM (1)','uploads/documents/3_WhatsApp Image 2026-07-30 at 3.15.36 PM (1).jpeg',3,NULL,NULL,NULL,'2026-10-13 03:41:09','active',NULL,1,NULL,'2026-10-13 03:41:09','2026-10-13 03:41:09'),(4,'photo',0,'WhatsApp Image 2026-07-30 at 3.15.36 PM','uploads/documents/3_WhatsApp Image 2026-07-30 at 3.15.36 PM.jpeg',3,NULL,NULL,NULL,'2026-10-13 03:41:09','active',NULL,1,NULL,'2026-10-13 03:41:09','2026-10-13 03:41:09'),(5,'vehicle_cr',0,'WhatsApp Image 2026-10-06 at 12.10.04 PM.jpeg','uploads/documents/WhatsApp Image 2026-10-06 at 12.10.04 PM.jpeg.jpeg',2,2,NULL,1,'2026-10-13 03:53:26','active',NULL,1,NULL,'2026-10-13 03:53:26','2026-10-13 03:53:26'),(6,'bank_statement',0,'WhatsApp Image 2026-10-06 at 12.10.04 PM','uploads/documents/WhatsApp Image 2026-10-06 at 12.10.04 PM.jpeg',2,2,3,1,'2026-10-13 03:53:28','active',NULL,1,NULL,'2026-10-13 03:53:28','2026-10-13 03:53:28'),(7,'salary_confirmation_letter',0,'Mariyathas Savariyahhii','uploads/documents/Mariyathas Savariyahhii.pdf',2,2,4,1,'2026-10-13 03:53:30','active',NULL,1,NULL,'2026-10-13 03:53:30','2026-10-13 03:53:30'),(8,'property_deed',0,'WhatsApp Image 2026-10-09 at 9.06.59 AM.jpeg','uploads/documents/WhatsApp Image 2026-10-09 at 9.06.59 AM.jpeg.jpeg',4,3,NULL,1,'2026-10-13 05:15:32','active',NULL,1,NULL,'2026-10-13 05:15:32','2026-10-13 05:15:32'),(9,'nic_copy',0,'WhatsApp Image 2026-10-09 at 9.06.59 AM','uploads/documents/WhatsApp Image 2026-10-09 at 9.06.59 AM.jpeg',4,3,5,1,'2026-10-13 05:15:38','active',NULL,1,NULL,'2026-10-13 05:15:38','2026-10-13 05:15:38'),(10,'nic_copy',0,'WhatsApp Image 2026-10-06 at 12.10.04 PM','uploads/documents/WhatsApp Image 2026-10-06 at 12.10.04 PM.jpeg',4,3,6,1,'2026-10-13 05:15:44','active',NULL,1,NULL,'2026-10-13 05:15:44','2026-10-13 05:15:44'),(11,'valuation_report',0,'DOCUMENT Check list_260709_184626.pdf','uploads/documents/DOCUMENT Check list_260709_184626.pdf.pdf',4,4,NULL,1,'2026-10-14 06:37:35','active',NULL,1,NULL,'2026-10-14 06:37:35','2026-10-14 06:37:35'),(12,'property_deed',0,'DOCUMENT Check list_260709_184626.pdf','uploads/documents/DOCUMENT Check list_260709_184626.pdf.pdf',4,4,NULL,1,'2026-10-14 06:37:37','active',NULL,1,NULL,'2026-10-14 06:37:37','2026-10-14 06:37:37'),(13,'vehicle_cr',0,'mermaid-diagram.png','uploads/documents/mermaid-diagram.png.png',6,5,NULL,1,'2026-10-14 07:40:56','active',NULL,1,NULL,'2026-10-14 07:40:56','2026-10-14 07:40:56');
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `f_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `l_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_with_initials` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reporting_manager_id` bigint unsigned DEFAULT NULL,
  `province_id` bigint unsigned DEFAULT NULL,
  `region_id` bigint unsigned DEFAULT NULL,
  `zonal_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `designation_id` bigint unsigned DEFAULT NULL,
  `employee_type` enum('permanent','contract','internship','probation') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_type` enum('nic','passport','driving_license','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sri Lanka',
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_primary` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_secondary` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `have_whatsapp` tinyint(1) NOT NULL DEFAULT '0',
  `whatsapp_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_id_number_unique` (`id_number`),
  UNIQUE KEY `employees_employee_code_unique` (`employee_code`),
  UNIQUE KEY `employees_email_unique` (`email`),
  KEY `employees_reporting_manager_id_foreign` (`reporting_manager_id`),
  KEY `employees_province_id_foreign` (`province_id`),
  KEY `employees_region_id_foreign` (`region_id`),
  KEY `employees_zonal_id_foreign` (`zonal_id`),
  KEY `employees_branch_id_foreign` (`branch_id`),
  KEY `employees_department_id_foreign` (`department_id`),
  KEY `employees_designation_id_foreign` (`designation_id`),
  CONSTRAINT `employees_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_reporting_manager_id_foreign` FOREIGN KEY (`reporting_manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_zonal_id_foreign` FOREIGN KEY (`zonal_id`) REFERENCES `zonals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `external_recovery_agents`
--

DROP TABLE IF EXISTS `external_recovery_agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_recovery_agents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `external_recovery_agents`
--

LOCK TABLES `external_recovery_agents` WRITE;
/*!40000 ALTER TABLE `external_recovery_agents` DISABLE KEYS */;
/*!40000 ALTER TABLE `external_recovery_agents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fixed_assests`
--

DROP TABLE IF EXISTS `fixed_assests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fixed_assests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `owner_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `property_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `market_value` decimal(15,2) DEFAULT NULL,
  `is_mortaged` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fixed_assests_slug_unique` (`slug`),
  KEY `fixed_assests_customer_id_foreign` (`customer_id`),
  CONSTRAINT `fixed_assests_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fixed_assests`
--

LOCK TABLES `fixed_assests` WRITE;
/*!40000 ALTER TABLE `fixed_assests` DISABLE KEYS */;
INSERT INTO `fixed_assests` VALUES (1,'AST-FIX-0001',2,'Nilukshiya','Colombo 07','12 Perches',1000000.00,0,1,NULL,'2026-10-13 03:37:48','2026-10-13 03:37:48'),(2,'AST-FIX-0002',3,'Karuna','Colombo 03','10 Perches',0.00,0,1,NULL,'2026-10-13 03:41:08','2026-10-13 03:41:08'),(3,'AST-FIX-0003',4,'Mithurjan','Colombo 02',NULL,0.00,0,1,NULL,'2026-10-13 05:10:22','2026-10-13 05:10:22');
/*!40000 ALTER TABLE `fixed_assests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_loan_items`
--

DROP TABLE IF EXISTS `group_loan_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_loan_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_loan_id` bigint unsigned NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int unsigned NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `line_total` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `group_loan_items_group_loan_id_foreign` (`group_loan_id`),
  CONSTRAINT `group_loan_items_group_loan_id_foreign` FOREIGN KEY (`group_loan_id`) REFERENCES `group_loans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_loan_items`
--

LOCK TABLES `group_loan_items` WRITE;
/*!40000 ALTER TABLE `group_loan_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_loan_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_loans`
--

DROP TABLE IF EXISTS `group_loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_loans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_loan_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `loan_product_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_of_members` int unsigned NOT NULL,
  `competency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_amount` decimal(15,2) NOT NULL,
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `service_charge_percentage` decimal(6,3) NOT NULL,
  `term_months` int unsigned NOT NULL,
  `service_charge_amount` decimal(15,2) DEFAULT NULL,
  `total_repayment_amount` decimal(15,2) DEFAULT NULL,
  `amount_per_member` decimal(15,2) DEFAULT NULL,
  `applied_by` bigint unsigned DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `assigned_reviewer_id` bigint unsigned DEFAULT NULL,
  `reviewed_remarks` text COLLATE utf8mb4_unicode_ci,
  `verified_remarks` text COLLATE utf8mb4_unicode_ci,
  `approval_remarks` text COLLATE utf8mb4_unicode_ci,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `review_failure_reason` text COLLATE utf8mb4_unicode_ci,
  `review_failed_at` timestamp NULL DEFAULT NULL,
  `resubmitted_at` timestamp NULL DEFAULT NULL,
  `resubmission_count` int unsigned NOT NULL DEFAULT '0',
  `verify_failure_reason` text COLLATE utf8mb4_unicode_ci,
  `verify_failed_at` timestamp NULL DEFAULT NULL,
  `reverify_count` int unsigned NOT NULL DEFAULT '0',
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `disbursed_at` timestamp NULL DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_loans_group_loan_no_unique` (`group_loan_no`),
  KEY `group_loans_loan_product_id_foreign` (`loan_product_id`),
  KEY `group_loans_applied_by_foreign` (`applied_by`),
  KEY `group_loans_reviewed_by_foreign` (`reviewed_by`),
  KEY `group_loans_verified_by_foreign` (`verified_by`),
  KEY `group_loans_approved_by_foreign` (`approved_by`),
  KEY `group_loans_assigned_reviewer_id_foreign` (`assigned_reviewer_id`),
  KEY `group_loans_branch_id_status_index` (`branch_id`,`status`),
  KEY `group_loans_status_index` (`status`),
  CONSTRAINT `group_loans_applied_by_foreign` FOREIGN KEY (`applied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `group_loans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `group_loans_assigned_reviewer_id_foreign` FOREIGN KEY (`assigned_reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `group_loans_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `group_loans_loan_product_id_foreign` FOREIGN KEY (`loan_product_id`) REFERENCES `loan_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_loans_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `group_loans_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_loans`
--

LOCK TABLES `group_loans` WRITE;
/*!40000 ALTER TABLE `group_loans` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groups_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guarantors`
--

DROP TABLE IF EXISTS `guarantors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guarantors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone_primary` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_registration_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_joined` date DEFAULT NULL,
  `salary` decimal(15,2) DEFAULT NULL,
  `allowance` decimal(15,2) DEFAULT NULL,
  `other_income` decimal(15,2) DEFAULT NULL,
  `liabilities` decimal(15,2) DEFAULT NULL,
  `bank_name_of_guarantor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_no_of_guarantor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_branch_of_guarantor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guarantors_customer_id_foreign` (`customer_id`),
  CONSTRAINT `guarantors_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guarantors`
--

LOCK TABLES `guarantors` WRITE;
/*!40000 ALTER TABLE `guarantors` DISABLE KEYS */;
INSERT INTO `guarantors` VALUES (1,1,'Rifkey','guarantor_1','NIC','200012436587',NULL,NULL,'0762570074','Employed','SE','CDP',NULL,NULL,NULL,NULL,100000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-12 10:57:20','2026-10-12 10:57:20'),(2,1,'Ifham','guarantor_2','NIC','200123456789',NULL,NULL,'0762570074','Employed','SE','CDP',NULL,NULL,NULL,NULL,100000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-12 10:57:22','2026-10-12 10:57:22'),(3,2,'Rifkey','guarantor_1','NIC','199812345678',NULL,'2026-10-01','0762570074','Employed','SE','CDP',NULL,NULL,NULL,NULL,100000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 03:53:26','2026-10-13 03:53:26'),(4,2,'Ifham','guarantor_2','NIC','199887654321',NULL,NULL,'0762570074','Employed','SE','CDP',NULL,NULL,NULL,NULL,100000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 03:53:29','2026-10-13 03:53:29'),(5,4,'Sulani','guarantor_1','NIC','199612345678',NULL,'2026-10-08','0762570074','Employed','SE','CDP',NULL,NULL,NULL,NULL,100000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 05:15:34','2026-10-13 05:15:34'),(6,4,'Changana','guarantor_2','NIC','200045678909',NULL,NULL,'0762570074','Employed','SE','CDP',NULL,NULL,NULL,NULL,100000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 05:15:40','2026-10-13 05:15:40'),(7,4,'Karuna','guarantor_1','NIC','123456789012',NULL,'2026-10-02','0762570074','Employed','SE','CDP',NULL,NULL,NULL,NULL,100000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-14 06:37:39','2026-10-14 06:37:39'),(8,4,'Sahi','guarantor_2','NIC','987654321232',NULL,NULL,'0762570074','Employed','SE','CDP',NULL,NULL,NULL,NULL,100000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-14 06:37:43','2026-10-14 06:37:43'),(9,6,'Vishnu','guarantor_1','NIC','199612345678',NULL,NULL,'0762570074','Employed','SE','CDP',NULL,NULL,NULL,NULL,1000000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-14 07:40:58','2026-10-14 07:40:58'),(10,6,'Kamal','guarantor_2','NIC','199512345678',NULL,NULL,'07632570074','Employed','SE','CDP',NULL,NULL,NULL,NULL,100000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-14 07:41:02','2026-10-14 07:41:02');
/*!40000 ALTER TABLE `guarantors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legal_document_templates`
--

DROP TABLE IF EXISTS `legal_document_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_document_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_type_id` bigint unsigned NOT NULL,
  `document_type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `legal_templates_loan_type_document_language_unique` (`loan_type_id`,`document_type`,`language`),
  KEY `legal_document_templates_created_by_foreign` (`created_by`),
  KEY `legal_document_templates_document_type_index` (`document_type`),
  KEY `legal_document_templates_language_index` (`language`),
  CONSTRAINT `legal_document_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `legal_document_templates_loan_type_id_foreign` FOREIGN KEY (`loan_type_id`) REFERENCES `loan_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_document_templates`
--

LOCK TABLES `legal_document_templates` WRITE;
/*!40000 ALTER TABLE `legal_document_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `legal_document_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legal_documents`
--

DROP TABLE IF EXISTS `legal_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint unsigned DEFAULT NULL,
  `legal_created_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `legal_updated_at` timestamp NULL DEFAULT NULL,
  `loan_application_id` bigint unsigned NOT NULL,
  `legal_document_template_id` bigint unsigned DEFAULT NULL,
  `reference_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `legal_document_created_date` timestamp NULL DEFAULT NULL,
  `details` json DEFAULT NULL,
  `printed_count` int unsigned NOT NULL DEFAULT '0',
  `last_printed_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `legal_documents_reference_no_unique` (`reference_no`),
  KEY `legal_documents_created_by_foreign` (`created_by`),
  KEY `legal_documents_updated_by_foreign` (`updated_by`),
  KEY `legal_documents_legal_document_template_id_foreign` (`legal_document_template_id`),
  KEY `legal_documents_application_type_index` (`loan_application_id`,`document_type`),
  KEY `legal_documents_document_type_index` (`document_type`),
  KEY `legal_documents_status_index` (`status`),
  CONSTRAINT `legal_documents_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `legal_documents_legal_document_template_id_foreign` FOREIGN KEY (`legal_document_template_id`) REFERENCES `legal_document_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `legal_documents_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `legal_documents_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_documents`
--

LOCK TABLES `legal_documents` WRITE;
/*!40000 ALTER TABLE `legal_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `legal_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lending_mortgages`
--

DROP TABLE IF EXISTS `lending_mortgages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lending_mortgages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('CDP investment','Property','Vehicle') COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lending_mortgages_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lending_mortgages`
--

LOCK TABLES `lending_mortgages` WRITE;
/*!40000 ALTER TABLE `lending_mortgages` DISABLE KEYS */;
INSERT INTO `lending_mortgages` VALUES (1,'CDP investment','Investment Plan 1','CDP-INV-001',50.00,'active','2026-10-12 10:45:59','2026-10-12 10:45:59'),(2,'CDP investment','Investment Plan 2','CDP-INV-002',60.00,'active','2026-10-12 10:45:59','2026-10-12 10:45:59'),(3,'CDP investment','Investment Plan 3','CDP-INV-003',70.00,'active','2026-10-12 10:45:59','2026-10-12 10:45:59'),(4,'Property','Property Mortgage Plan 1','CDP-PRO-001',70.00,'active','2026-10-12 10:45:59','2026-10-12 10:45:59'),(5,'Property','Property Mortgage Plan 2','CDP-PRO-002',50.00,'active','2026-10-12 10:45:59','2026-10-12 10:45:59'),(6,'Property','Property Mortgage Plan 3','CDP-PRO-003',60.00,'active','2026-10-12 10:45:59','2026-10-12 10:45:59'),(7,'Vehicle','Vehicle Plan 1','CDP-VEC-001',70.00,'active','2026-10-12 10:45:59','2026-10-12 10:45:59'),(8,'Vehicle','Vehicle Plan 2','CDP-VEC-002',50.00,'active','2026-10-12 10:45:59','2026-10-12 10:45:59'),(9,'Vehicle','Vehicle Plan 3','CDP-VEC-003',60.00,'active','2026-10-12 10:45:59','2026-10-12 10:45:59');
/*!40000 ALTER TABLE `lending_mortgages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `liabilities`
--

DROP TABLE IF EXISTS `liabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `liabilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `liability_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `institution_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_reference_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_amount` decimal(15,2) DEFAULT NULL,
  `outstanding_balance` decimal(15,2) NOT NULL,
  `monthly_installment` decimal(15,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `liabilities_customer_id_foreign` (`customer_id`),
  CONSTRAINT `liabilities_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `liabilities`
--

LOCK TABLES `liabilities` WRITE;
/*!40000 ALTER TABLE `liabilities` DISABLE KEYS */;
INSERT INTO `liabilities` VALUES (1,2,'bank_loan','HNB','1234-5678-9012',1000000.00,75000.00,20000.00,'2026-10-02','2026-10-31',1,NULL,'2026-10-13 03:37:48','2026-10-13 03:37:48');
/*!40000 ALTER TABLE `liabilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_application_customers`
--

DROP TABLE IF EXISTS `loan_application_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_application_customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `member_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gn_division` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ds_division` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_app_customer_unique` (`loan_application_id`,`customer_id`),
  KEY `loan_application_customers_customer_id_foreign` (`customer_id`),
  CONSTRAINT `loan_application_customers_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_application_customers_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_application_customers`
--

LOCK TABLES `loan_application_customers` WRITE;
/*!40000 ALTER TABLE `loan_application_customers` DISABLE KEYS */;
INSERT INTO `loan_application_customers` VALUES (1,2,2,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 03:53:25','2026-10-13 03:53:25'),(2,2,3,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 03:53:25','2026-10-13 03:53:25');
/*!40000 ALTER TABLE `loan_application_customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_application_guarantors`
--

DROP TABLE IF EXISTS `loan_application_guarantors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_application_guarantors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned NOT NULL,
  `guarantor_id` bigint unsigned NOT NULL,
  `guarantor_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_app_guarantor_unique` (`loan_application_id`,`guarantor_id`),
  KEY `loan_application_guarantors_guarantor_id_foreign` (`guarantor_id`),
  CONSTRAINT `loan_application_guarantors_guarantor_id_foreign` FOREIGN KEY (`guarantor_id`) REFERENCES `guarantors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_application_guarantors_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_application_guarantors`
--

LOCK TABLES `loan_application_guarantors` WRITE;
/*!40000 ALTER TABLE `loan_application_guarantors` DISABLE KEYS */;
INSERT INTO `loan_application_guarantors` VALUES (1,1,1,'guarantor_1','pending',NULL,'2026-10-12 10:57:21','2026-10-12 10:57:21'),(2,1,2,'guarantor_2','pending',NULL,'2026-10-12 10:57:23','2026-10-12 10:57:23'),(3,2,3,'guarantor_1','pending',NULL,'2026-10-13 03:53:27','2026-10-13 03:53:27'),(4,2,4,'guarantor_2','pending',NULL,'2026-10-13 03:53:30','2026-10-13 03:53:30'),(5,3,5,'guarantor_1','pending',NULL,'2026-10-13 05:15:36','2026-10-13 05:15:36'),(6,3,6,'guarantor_2','pending',NULL,'2026-10-13 05:15:42','2026-10-13 05:15:42'),(7,4,7,'guarantor_1','pending',NULL,'2026-10-14 06:37:41','2026-10-14 06:37:41'),(8,4,8,'guarantor_2','pending',NULL,'2026-10-14 06:37:45','2026-10-14 06:37:45'),(9,5,9,'guarantor_1','pending',NULL,'2026-10-14 07:41:00','2026-10-14 07:41:00'),(10,5,10,'guarantor_2','pending',NULL,'2026-10-14 07:41:04','2026-10-14 07:41:04');
/*!40000 ALTER TABLE `loan_application_guarantors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_application_securities`
--

DROP TABLE IF EXISTS `loan_application_securities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_application_securities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned NOT NULL,
  `security_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `security_plan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lending_mortgage_id` bigint unsigned DEFAULT NULL,
  `investment_nic` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `policy_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `investment_details` json DEFAULT NULL,
  `owner_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property_type` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_deed_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimated_value` decimal(15,2) DEFAULT NULL,
  `evaluation_date` date DEFAULT NULL,
  `evaluated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `evaluation_remarks` text COLLATE utf8mb4_unicode_ci,
  `vehicle_make` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year_of_manufacture` smallint unsigned DEFAULT NULL,
  `year_of_registration` smallint unsigned DEFAULT NULL,
  `registration_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chassis_engine_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_value` decimal(15,2) DEFAULT NULL,
  `pledged_value` decimal(15,2) DEFAULT NULL,
  `max_loan_percentage` decimal(5,2) DEFAULT NULL,
  `max_loan_amount` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_application_securities_loan_application_id_foreign` (`loan_application_id`),
  KEY `loan_application_securities_security_type_index` (`security_type`),
  KEY `loan_application_securities_policy_number_index` (`policy_number`),
  KEY `loan_application_securities_registration_number_index` (`registration_number`),
  KEY `loan_application_securities_lending_mortgage_id_foreign` (`lending_mortgage_id`),
  CONSTRAINT `loan_application_securities_lending_mortgage_id_foreign` FOREIGN KEY (`lending_mortgage_id`) REFERENCES `lending_mortgages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_application_securities_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_application_securities`
--

LOCK TABLES `loan_application_securities` WRITE;
/*!40000 ALTER TABLE `loan_application_securities` DISABLE KEYS */;
INSERT INTO `loan_application_securities` VALUES (1,2,'vehicle',NULL,8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Toyota','Vezel',2018,2019,'WP-CE-1234','NZE141-9876',6500000.00,6500000.00,50.00,3250000.00,'2026-10-13 03:53:25','2026-10-13 03:53:25'),(2,3,'property_mortgage',NULL,5,NULL,NULL,NULL,'Mithu','house','DEED-KL-01-0002',45000000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,45000000.00,50.00,22500000.00,'2026-10-13 05:15:30','2026-10-13 05:15:30'),(3,4,'property_mortgage','CDP-PRO-001',4,NULL,NULL,NULL,'Thipanu','house','DEED_KL_03009',4000000.00,'2026-10-02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,4000000.00,70.00,2800000.00,'2026-10-14 06:37:33','2026-10-14 06:37:33'),(4,5,'vehicle','CDP-VEC-002',8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Toyota','Vezel',2019,2019,'1234','1234',6000000.00,6000000.00,50.00,3000000.00,'2026-10-14 07:40:54','2026-10-14 07:40:54');
/*!40000 ALTER TABLE `loan_application_securities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_application_status_history`
--

DROP TABLE IF EXISTS `loan_application_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_application_status_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `loan_application_id` bigint unsigned NOT NULL,
  `loan_application_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` bigint unsigned NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `change_date` date NOT NULL,
  `changed_at` timestamp NOT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_application_status_history_changed_by_foreign` (`changed_by`),
  KEY `loan_app_status_history_app_id_created_at_idx` (`loan_application_id`,`created_at`),
  KEY `loan_app_status_history_application_id_idx` (`application_id`,`changed_at`),
  KEY `loan_app_status_history_date_status_idx` (`change_date`,`loan_application_status`),
  CONSTRAINT `loan_application_status_history_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_application_status_history_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loan_application_status_history_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_application_status_history`
--

LOCK TABLES `loan_application_status_history` WRITE;
/*!40000 ALTER TABLE `loan_application_status_history` DISABLE KEYS */;
INSERT INTO `loan_application_status_history` VALUES (1,1,1,'submitted',1,'Loan application submitted','2026-10-12','2026-10-12 10:57:20',NULL,'2026-10-12 10:57:20','2026-10-12 10:57:20'),(2,2,2,'submitted',1,'Loan application submitted','2026-10-13','2026-10-13 03:53:25',NULL,'2026-10-13 03:53:25','2026-10-13 03:53:25'),(3,3,3,'submitted',1,'Loan application submitted','2026-10-13','2026-10-13 05:15:30',NULL,'2026-10-13 05:15:30','2026-10-13 05:15:30'),(7,3,3,'cancelled',1,'Customer has change his mind','2026-10-14','2026-10-14 05:13:23',NULL,'2026-10-14 05:13:23','2026-10-14 05:13:23'),(8,4,4,'submitted',1,'Loan application submitted','2026-10-14','2026-10-14 06:37:33',NULL,'2026-10-14 06:37:33','2026-10-14 06:37:33'),(9,4,4,'reviewed',1,'Good','2026-10-14','2026-10-14 06:38:44',NULL,'2026-10-14 06:38:44','2026-10-14 06:38:44'),(10,4,4,'verified',1,'good','2026-10-14','2026-10-14 06:38:57',NULL,'2026-10-14 06:38:57','2026-10-14 06:38:57'),(11,4,4,'approved',1,'not eligible','2026-10-14','2026-10-14 06:39:49',NULL,'2026-10-14 06:39:49','2026-10-14 06:39:49'),(12,4,4,'accepted',1,'fine','2026-10-14','2026-10-14 06:40:04',NULL,'2026-10-14 06:40:04','2026-10-14 06:40:04'),(13,5,5,'submitted',1,'Loan application submitted','2026-10-14','2026-10-14 07:40:54',NULL,'2026-10-14 07:40:54','2026-10-14 07:40:54'),(14,5,5,'reviewed',1,'good','2026-10-14','2026-10-14 08:16:21',NULL,'2026-10-14 08:16:21','2026-10-14 08:16:21'),(15,5,5,'verified',1,'verify','2026-10-14','2026-10-14 08:16:33',NULL,'2026-10-14 08:16:33','2026-10-14 08:16:33'),(16,5,5,'approved',1,'good','2026-10-14','2026-10-14 08:16:49',NULL,'2026-10-14 08:16:49','2026-10-14 08:16:49'),(17,5,5,'on_hold',1,'discussion','2026-10-14','2026-10-14 08:17:05',NULL,'2026-10-14 08:17:05','2026-10-14 08:17:05'),(18,5,5,'accepted',1,'accept','2026-10-14','2026-10-14 08:17:17',NULL,'2026-10-14 08:17:17','2026-10-14 08:17:17');
/*!40000 ALTER TABLE `loan_application_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_applications`
--

DROP TABLE IF EXISTS `loan_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `loan_product_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `group_loan_id` bigint unsigned DEFAULT NULL,
  `requested_amount` decimal(15,2) NOT NULL,
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `interest_rate` decimal(6,3) DEFAULT NULL,
  `interest_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'flat',
  `term_months` int unsigned NOT NULL,
  `monthly_installment` decimal(15,2) DEFAULT NULL,
  `processing_fee` decimal(10,2) DEFAULT NULL,
  `net_disbursement_amount` decimal(15,2) DEFAULT NULL,
  `monthly_repayment_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `applied_by` bigint unsigned DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `reviewed_remarks` text COLLATE utf8mb4_unicode_ci,
  `verified_remarks` text COLLATE utf8mb4_unicode_ci,
  `approval_remarks` text COLLATE utf8mb4_unicode_ci,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `review_failure_reason` text COLLATE utf8mb4_unicode_ci,
  `review_failed_at` timestamp NULL DEFAULT NULL,
  `resubmitted_at` timestamp NULL DEFAULT NULL,
  `resubmission_count` int unsigned NOT NULL DEFAULT '0',
  `verify_failure_reason` text COLLATE utf8mb4_unicode_ci,
  `verify_failed_at` timestamp NULL DEFAULT NULL,
  `reverify_count` int unsigned NOT NULL DEFAULT '0',
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `disbursed_at` timestamp NULL DEFAULT NULL,
  `offer_responded_by` bigint unsigned DEFAULT NULL,
  `offer_responded_at` timestamp NULL DEFAULT NULL,
  `offer_decline_reason` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offer_remarks` text COLLATE utf8mb4_unicode_ci,
  `outstanding_balance` decimal(15,2) DEFAULT NULL,
  `approval_reference_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recommended_by_employee_id` bigint unsigned DEFAULT NULL,
  `recommender_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recommender_employee_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recommender_nic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recommender_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `assigned_reviewer_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_applications_approval_reference_no_unique` (`approval_reference_no`),
  KEY `loan_applications_application_id_foreign` (`application_id`),
  KEY `loan_applications_customer_id_foreign` (`customer_id`),
  KEY `loan_applications_loan_product_id_foreign` (`loan_product_id`),
  KEY `loan_applications_group_loan_id_foreign` (`group_loan_id`),
  KEY `loan_applications_applied_by_foreign` (`applied_by`),
  KEY `loan_applications_reviewed_by_foreign` (`reviewed_by`),
  KEY `loan_applications_verified_by_foreign` (`verified_by`),
  KEY `loan_applications_approved_by_foreign` (`approved_by`),
  KEY `loan_applications_offer_responded_by_foreign` (`offer_responded_by`),
  KEY `loan_applications_recommended_by_employee_id_foreign` (`recommended_by_employee_id`),
  KEY `loan_applications_assigned_reviewer_id_foreign` (`assigned_reviewer_id`),
  KEY `loan_applications_branch_id_status_index` (`branch_id`,`status`),
  KEY `loan_applications_status_index` (`status`),
  CONSTRAINT `loan_applications_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_applications_applied_by_foreign` FOREIGN KEY (`applied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_applications_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_applications_assigned_reviewer_id_foreign` FOREIGN KEY (`assigned_reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_applications_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_applications_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_applications_group_loan_id_foreign` FOREIGN KEY (`group_loan_id`) REFERENCES `group_loans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_applications_loan_product_id_foreign` FOREIGN KEY (`loan_product_id`) REFERENCES `loan_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_applications_offer_responded_by_foreign` FOREIGN KEY (`offer_responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_applications_recommended_by_employee_id_foreign` FOREIGN KEY (`recommended_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_applications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_applications_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_applications`
--

LOCK TABLES `loan_applications` WRITE;
/*!40000 ALTER TABLE `loan_applications` DISABLE KEYS */;
INSERT INTO `loan_applications` VALUES (1,1,1,1,1,NULL,500000.00,NULL,10.000,'flat',12,NULL,1000.00,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,0,'2026-10-12 10:57:20',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'submitted',NULL,1,NULL,'2026-10-12 10:57:20','2026-10-12 10:57:20'),(2,2,2,1,1,NULL,500000.00,NULL,10.000,'flat',12,NULL,1000.00,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,0,'2026-10-13 03:53:25',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'submitted',NULL,1,NULL,'2026-10-13 03:53:25','2026-10-13 03:53:25'),(3,3,4,1,1,NULL,500000.00,NULL,10.000,'flat',12,NULL,1000.00,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,0,'2026-10-13 05:15:30',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'cancelled',NULL,0,NULL,'2026-10-13 05:15:30','2026-10-14 05:13:23'),(4,4,4,1,1,NULL,500000.00,300000.00,10.000,'flat',12,27500.00,1000.00,299000.00,NULL,1,1,1,1,'Good','good','not eligible',NULL,NULL,NULL,NULL,0,NULL,NULL,0,'2026-10-14 06:37:33','2026-10-14 06:38:44','2026-10-14 06:38:57','2026-10-14 06:39:49',NULL,1,'2026-10-14 06:40:04',NULL,'fine',330000.00,'CDP-HCOL-2610140001',NULL,NULL,NULL,NULL,NULL,'accepted',NULL,1,NULL,'2026-10-14 06:37:33','2026-10-14 06:40:04'),(5,5,6,1,1,NULL,500000.00,400000.00,10.000,'flat',12,36666.67,1000.00,399000.00,NULL,1,1,1,1,'good','verify','good',NULL,NULL,NULL,NULL,0,NULL,NULL,0,'2026-10-14 07:40:54','2026-10-14 08:16:21','2026-10-14 08:16:33','2026-10-14 08:16:49',NULL,1,'2026-10-14 08:17:17',NULL,'accept',440000.00,'CDP-HCOL-2610140002',NULL,NULL,NULL,NULL,NULL,'accepted',NULL,1,NULL,'2026-10-14 07:40:54','2026-10-14 08:17:17');
/*!40000 ALTER TABLE `loan_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_customer_confirmations`
--

DROP TABLE IF EXISTS `loan_customer_confirmations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_customer_confirmations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `phone_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_expires_at` datetime NOT NULL,
  `sms_sent` tinyint(1) NOT NULL DEFAULT '0',
  `viewed_at` datetime DEFAULT NULL,
  `snapshot_data` json NOT NULL,
  `snapshot_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `otp_attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `otp_requests` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `declaration_text` text COLLATE utf8mb4_unicode_ci,
  `confirmed_at` datetime DEFAULT NULL,
  `confirmed_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confirmed_user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_customer_confirmations_token_hash_unique` (`token_hash`),
  KEY `loan_customer_confirmations_customer_id_foreign` (`customer_id`),
  KEY `loan_customer_confirmations_created_by_foreign` (`created_by`),
  KEY `lcc_loan_customer_status_idx` (`loan_application_id`,`customer_id`,`status`),
  CONSTRAINT `loan_customer_confirmations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_customer_confirmations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_customer_confirmations_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_customer_confirmations`
--

LOCK TABLES `loan_customer_confirmations` WRITE;
/*!40000 ALTER TABLE `loan_customer_confirmations` DISABLE KEYS */;
INSERT INTO `loan_customer_confirmations` VALUES (1,1,1,1,'0752932640','e5e7e0cbfeccd02bda8e9ceb17b89bc5d89aa16550c1f50928db67a011063628','2026-10-15 16:41:06',1,'2026-10-12 16:49:17','{\"kyc\": {\"id\": 1, \"email\": \"piranya105@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Piranya\", \"id_number\": \"200012345678\", \"employment\": {\"status\": null, \"occupation\": null, \"employer_name\": null, \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0001\", \"customer_code\": \"CUS0001\", \"date_of_birth\": \"2026-10-03\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.Piranya\", \"preferred_language\": \"Tamil\"}, \"loan\": {\"id\": 1, \"product\": \"Personal\", \"loan_type\": null, \"reference\": \"APP-HCOL-2610120001\", \"guarantors\": [{\"type\": \"guarantor_1\", \"full_name\": \"Rifkey\"}, {\"type\": \"guarantor_2\", \"full_name\": \"Ifham\"}], \"securities\": [], \"term_months\": 12, \"co_borrowers\": [], \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"processing_fee\": \"1000.00\", \"requested_amount\": \"500000.00\", \"monthly_installment\": null, \"monthly_repayment_date\": null, \"net_disbursement_amount\": null}}','47c5735765a61c1d64f521908665d8449786dd8e8942ed19461c44ba184e3d63',NULL,'2026-10-12 16:59:49',0,1,'confirmed','I confirm that the personal (KYC) details and the loan application details shown above are true and correct, and that I have applied for this loan of my own free will. I understand that this confirmation does not mean the loan has been approved.','2026-10-12 16:50:08','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-12 11:11:06','2026-10-12 11:20:08'),(2,2,2,1,'0752932640','b9e1f06df359a0ae295bd2013b5f46de9afb434f58be5cf96b5170e7a91ed656','2026-10-16 09:31:12',1,NULL,'{\"kyc\": {\"id\": 2, \"email\": \"piranyacdp@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Nilukshi\", \"id_number\": \"199712345678\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0002\", \"customer_code\": \"CUS0002\", \"date_of_birth\": \"2026-10-02\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.Nilukshi\", \"preferred_language\": \"Sinhala\"}, \"loan\": {\"id\": 2, \"product\": \"Personal\", \"loan_type\": null, \"reference\": \"APP-HCOL-2610130001\", \"guarantors\": [{\"type\": \"guarantor_1\", \"full_name\": \"Rifkey\"}, {\"type\": \"guarantor_2\", \"full_name\": \"Ifham\"}], \"securities\": [{\"type\": \"vehicle\", \"policy_number\": null, \"owner_deed_number\": null, \"registration_number\": \"WP-CE-1234\"}], \"term_months\": 12, \"co_borrowers\": [{\"full_name\": \"Nilukshi\", \"customer_id\": \"CUST-0002\"}, {\"full_name\": \"Karuna\", \"customer_id\": \"CUST-0003\"}], \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"processing_fee\": \"1000.00\", \"requested_amount\": \"500000.00\", \"monthly_installment\": null, \"monthly_repayment_date\": null, \"net_disbursement_amount\": null}}','6a188a7be50c2e77117abecfd893d618091b0a0b5a7ac4e225a74b8b67e3ddb5',NULL,NULL,0,0,'superseded',NULL,NULL,NULL,NULL,'2026-10-13 04:01:12','2026-10-13 04:01:42'),(3,2,3,1,'0752932640','0b03a6bf78839488ed32af013a1242f78c190fdcb7eaae372782691ace5168a6','2026-10-16 09:31:13',1,NULL,'{\"kyc\": {\"id\": 3, \"email\": null, \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Karuna\", \"id_number\": \"199512345678\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0003\", \"customer_code\": \"CUS0003\", \"date_of_birth\": \"2026-10-03\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"K.Karuna\", \"preferred_language\": \"Sinhala\"}, \"loan\": {\"id\": 2, \"product\": \"Personal\", \"loan_type\": null, \"reference\": \"APP-HCOL-2610130001\", \"guarantors\": [{\"type\": \"guarantor_1\", \"full_name\": \"Rifkey\"}, {\"type\": \"guarantor_2\", \"full_name\": \"Ifham\"}], \"securities\": [{\"type\": \"vehicle\", \"policy_number\": null, \"owner_deed_number\": null, \"registration_number\": \"WP-CE-1234\"}], \"term_months\": 12, \"co_borrowers\": [{\"full_name\": \"Nilukshi\", \"customer_id\": \"CUST-0002\"}, {\"full_name\": \"Karuna\", \"customer_id\": \"CUST-0003\"}], \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"processing_fee\": \"1000.00\", \"requested_amount\": \"500000.00\", \"monthly_installment\": null, \"monthly_repayment_date\": null, \"net_disbursement_amount\": null}}','5f758fab12a7daa49d8d0a197e83eb863c2f067b148e7b2a3a6ca3fdef7505ce',NULL,NULL,0,0,'superseded',NULL,NULL,NULL,NULL,'2026-10-13 04:01:13','2026-10-13 04:01:43'),(4,2,2,1,'0752932640','c138a563a15c3c6ef2de9038964e991f416f5d89333701b4410e3d6f88b9b2b1','2026-10-16 09:31:42',1,'2026-10-13 09:39:14','{\"kyc\": {\"id\": 2, \"email\": \"piranyacdp@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Nilukshi\", \"id_number\": \"199712345678\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0002\", \"customer_code\": \"CUS0002\", \"date_of_birth\": \"2026-10-02\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.Nilukshi\", \"preferred_language\": \"Sinhala\"}, \"loan\": {\"id\": 2, \"product\": \"Personal\", \"loan_type\": null, \"reference\": \"APP-HCOL-2610130001\", \"guarantors\": [{\"type\": \"guarantor_1\", \"full_name\": \"Rifkey\"}, {\"type\": \"guarantor_2\", \"full_name\": \"Ifham\"}], \"securities\": [{\"type\": \"vehicle\", \"policy_number\": null, \"owner_deed_number\": null, \"registration_number\": \"WP-CE-1234\"}], \"term_months\": 12, \"co_borrowers\": [{\"full_name\": \"Nilukshi\", \"customer_id\": \"CUST-0002\"}, {\"full_name\": \"Karuna\", \"customer_id\": \"CUST-0003\"}], \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"processing_fee\": \"1000.00\", \"requested_amount\": \"500000.00\", \"monthly_installment\": null, \"monthly_repayment_date\": null, \"net_disbursement_amount\": null}}','6a188a7be50c2e77117abecfd893d618091b0a0b5a7ac4e225a74b8b67e3ddb5','5679a93e8fbdffd96641d7abce7eca3fc2d5fb74a6bdc162af758b70f63c0e36','2026-10-13 10:59:28',0,1,'pending',NULL,NULL,NULL,NULL,'2026-10-13 04:01:42','2026-10-13 05:19:28'),(5,2,3,1,'0752932640','ebdafd578d3cfbb878bd115f0b0522544f7fc9ec33b9be6bbffe72b3885a134b','2026-10-16 09:31:43',1,NULL,'{\"kyc\": {\"id\": 3, \"email\": null, \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Karuna\", \"id_number\": \"199512345678\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0003\", \"customer_code\": \"CUS0003\", \"date_of_birth\": \"2026-10-03\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"K.Karuna\", \"preferred_language\": \"Sinhala\"}, \"loan\": {\"id\": 2, \"product\": \"Personal\", \"loan_type\": null, \"reference\": \"APP-HCOL-2610130001\", \"guarantors\": [{\"type\": \"guarantor_1\", \"full_name\": \"Rifkey\"}, {\"type\": \"guarantor_2\", \"full_name\": \"Ifham\"}], \"securities\": [{\"type\": \"vehicle\", \"policy_number\": null, \"owner_deed_number\": null, \"registration_number\": \"WP-CE-1234\"}], \"term_months\": 12, \"co_borrowers\": [{\"full_name\": \"Nilukshi\", \"customer_id\": \"CUST-0002\"}, {\"full_name\": \"Karuna\", \"customer_id\": \"CUST-0003\"}], \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"processing_fee\": \"1000.00\", \"requested_amount\": \"500000.00\", \"monthly_installment\": null, \"monthly_repayment_date\": null, \"net_disbursement_amount\": null}}','5f758fab12a7daa49d8d0a197e83eb863c2f067b148e7b2a3a6ca3fdef7505ce',NULL,NULL,0,0,'pending',NULL,NULL,NULL,NULL,'2026-10-13 04:01:43','2026-10-13 04:01:44'),(6,3,4,1,'0752932640','d39c7bd709f166f2b25c53e6f718b093f98318ebd7b7f7fbd35302877601eaa2','2026-10-16 10:46:44',1,'2026-10-13 10:47:37','{\"kyc\": {\"id\": 4, \"email\": \"cdppiranya@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Mithurjan\", \"id_number\": \"200312345678\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0004\", \"customer_code\": \"CUS0004\", \"date_of_birth\": \"2026-10-01\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.MIthurjan\", \"preferred_language\": \"Sinhala\"}, \"loan\": {\"id\": 3, \"product\": \"Personal\", \"loan_type\": \"Standard Borrowing\", \"reference\": \"APP-HCOL-2610130002\", \"guarantors\": [{\"type\": \"guarantor_1\", \"full_name\": \"Sulani\"}, {\"type\": \"guarantor_2\", \"full_name\": \"Changana\"}], \"securities\": [{\"type\": \"property_mortgage\", \"policy_number\": null, \"owner_deed_number\": \"DEED-KL-01-0002\", \"registration_number\": null}], \"term_months\": 12, \"co_borrowers\": [], \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"processing_fee\": \"1000.00\", \"requested_amount\": \"500000.00\", \"monthly_installment\": null, \"monthly_repayment_date\": null, \"net_disbursement_amount\": null}}','02ce24328aaf5fccbe10108fb1e26b96aa5ff1c1ffd591c065789b0ffba0c6c9',NULL,NULL,0,0,'superseded',NULL,NULL,NULL,NULL,'2026-10-13 05:16:44','2026-10-13 06:07:20'),(7,3,4,1,'0752932640','1217bd832baadbd8a017f0a0cd9083d2075e1fd1b3ba272b036b0fe1a635890a','2026-10-16 11:37:20',1,NULL,'{\"kyc\": {\"id\": 4, \"email\": \"cdppiranya@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Mithurjan\", \"id_number\": \"200312345678\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0004\", \"customer_code\": \"CUS0004\", \"date_of_birth\": \"2026-10-01\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.MIthurjan\", \"preferred_language\": \"Sinhala\"}, \"loan\": {\"id\": 3, \"product\": \"Personal\", \"loan_type\": \"Standard Borrowing\", \"reference\": \"APP-HCOL-2610130002\", \"guarantors\": [{\"type\": \"guarantor_1\", \"full_name\": \"Sulani\"}, {\"type\": \"guarantor_2\", \"full_name\": \"Changana\"}], \"securities\": [{\"type\": \"property_mortgage\", \"policy_number\": null, \"owner_deed_number\": \"DEED-KL-01-0002\", \"registration_number\": null}], \"term_months\": 12, \"co_borrowers\": [], \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"processing_fee\": \"1000.00\", \"requested_amount\": \"500000.00\", \"monthly_installment\": null, \"monthly_repayment_date\": null, \"net_disbursement_amount\": null}}','02ce24328aaf5fccbe10108fb1e26b96aa5ff1c1ffd591c065789b0ffba0c6c9',NULL,NULL,0,0,'superseded',NULL,NULL,NULL,NULL,'2026-10-13 06:07:20','2026-10-13 06:28:19'),(8,3,4,1,'0752932640','e281688787e07728c891b5e2b7eb85442e47a628cf3bac7e01ae27491a3b4113','2026-10-16 11:58:19',1,NULL,'{\"kyc\": {\"id\": 4, \"email\": \"cdppiranya@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Mithurjan\", \"id_number\": \"200312345678\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0004\", \"customer_code\": \"CUS0004\", \"date_of_birth\": \"2026-10-01\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.MIthurjan\", \"preferred_language\": \"Sinhala\"}, \"loan\": {\"id\": 3, \"product\": \"Personal\", \"loan_type\": \"Standard Borrowing\", \"reference\": \"APP-HCOL-2610130002\", \"guarantors\": [{\"type\": \"guarantor_1\", \"full_name\": \"Sulani\"}, {\"type\": \"guarantor_2\", \"full_name\": \"Changana\"}], \"securities\": [{\"type\": \"property_mortgage\", \"policy_number\": null, \"owner_deed_number\": \"DEED-KL-01-0002\", \"registration_number\": null}], \"term_months\": 12, \"co_borrowers\": [], \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"processing_fee\": \"1000.00\", \"requested_amount\": \"500000.00\", \"monthly_installment\": null, \"monthly_repayment_date\": null, \"net_disbursement_amount\": null}}','02ce24328aaf5fccbe10108fb1e26b96aa5ff1c1ffd591c065789b0ffba0c6c9',NULL,NULL,0,0,'superseded',NULL,NULL,NULL,NULL,'2026-10-13 06:28:19','2026-10-13 06:28:34'),(9,3,4,1,'0752932640','d8fbc55e00df6426b0ba94549fc75cd7f19ecdb8b8952a25d708badaa9095559','2026-10-16 11:58:34',1,NULL,'{\"kyc\": {\"id\": 4, \"email\": \"cdppiranya@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Mithurjan\", \"id_number\": \"200312345678\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0004\", \"customer_code\": \"CUS0004\", \"date_of_birth\": \"2026-10-01\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.MIthurjan\", \"preferred_language\": \"Sinhala\"}, \"loan\": {\"id\": 3, \"product\": \"Personal\", \"loan_type\": \"Standard Borrowing\", \"reference\": \"APP-HCOL-2610130002\", \"guarantors\": [{\"type\": \"guarantor_1\", \"full_name\": \"Sulani\"}, {\"type\": \"guarantor_2\", \"full_name\": \"Changana\"}], \"securities\": [{\"type\": \"property_mortgage\", \"policy_number\": null, \"owner_deed_number\": \"DEED-KL-01-0002\", \"registration_number\": null}], \"term_months\": 12, \"co_borrowers\": [], \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"processing_fee\": \"1000.00\", \"requested_amount\": \"500000.00\", \"monthly_installment\": null, \"monthly_repayment_date\": null, \"net_disbursement_amount\": null}}','02ce24328aaf5fccbe10108fb1e26b96aa5ff1c1ffd591c065789b0ffba0c6c9',NULL,NULL,0,0,'pending',NULL,NULL,NULL,NULL,'2026-10-13 06:28:34','2026-10-13 06:28:37'),(10,4,4,1,'0752932640','6989f48a1a446033726ce3b3798274aea2f61e0d33b17c054b1e3ca89c9f946a','2026-10-17 12:10:16',1,'2026-10-14 12:11:52','{\"kyc\": {\"id\": 4, \"email\": \"cdppiranya@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Mithurjan\", \"id_number\": \"200312345678\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0004\", \"customer_code\": \"CUS0004\", \"date_of_birth\": \"2026-10-01\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.MIthurjan\", \"preferred_language\": \"Sinhala\"}, \"loan\": {\"id\": 4, \"product\": \"Personal\", \"loan_type\": \"Standard Borrowing\", \"reference\": \"APP-HCOL-2610140001\", \"guarantors\": [{\"type\": \"guarantor_1\", \"full_name\": \"Karuna\"}, {\"type\": \"guarantor_2\", \"full_name\": \"Sahi\"}], \"securities\": [{\"type\": \"property_mortgage\", \"policy_number\": null, \"owner_deed_number\": \"DEED_KL_03009\", \"registration_number\": null}], \"term_months\": 12, \"co_borrowers\": [], \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"processing_fee\": \"1000.00\", \"requested_amount\": \"500000.00\", \"monthly_installment\": \"27500.00\", \"monthly_repayment_date\": null, \"net_disbursement_amount\": \"299000.00\"}}','ecf75a0331c0e774cf0e135c724547be9f60a67e84faa33593dd8130e455b0bd',NULL,'2026-10-14 12:23:09',0,1,'confirmed','I confirm that the personal (KYC) details and the loan application details shown above are true and correct, and that I have applied for this loan of my own free will. I understand that this confirmation does not mean the loan has been approved.','2026-10-14 12:13:27','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 06:40:16','2026-10-14 06:43:27'),(11,5,6,1,'0752932640','63f1410eef86872610fdb56923e67deb860497e52d0ef484e83ce326f4225c02','2026-10-17 13:47:27',1,'2026-10-14 13:49:14','{\"kyc\": {\"id\": 6, \"email\": null, \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Karinath\", \"id_number\": \"200012345679\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0006\", \"customer_code\": \"CUS0006\", \"date_of_birth\": \"2026-10-01\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Colombo\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"K.Karinath\", \"preferred_language\": \"Sinhala\"}, \"loan\": {\"id\": 5, \"product\": \"Personal\", \"loan_type\": \"Standard Borrowing\", \"reference\": \"APP-HCOL-2610140002\", \"guarantors\": [{\"type\": \"guarantor_1\", \"full_name\": \"Vishnu\"}, {\"type\": \"guarantor_2\", \"full_name\": \"Kamal\"}], \"securities\": [{\"type\": \"vehicle\", \"policy_number\": null, \"owner_deed_number\": null, \"registration_number\": \"1234\"}], \"term_months\": 12, \"co_borrowers\": [], \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"processing_fee\": \"1000.00\", \"requested_amount\": \"500000.00\", \"monthly_installment\": \"36666.67\", \"monthly_repayment_date\": null, \"net_disbursement_amount\": \"399000.00\"}}','cb03e8dcb3395f96de06e2d7658d92f72c02a0fe53d4c93e2d68450b35a2b911',NULL,NULL,0,0,'pending',NULL,NULL,NULL,NULL,'2026-10-14 08:17:27','2026-10-14 08:19:14');
/*!40000 ALTER TABLE `loan_customer_confirmations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_documents`
--

DROP TABLE IF EXISTS `loan_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned NOT NULL,
  `document_id` bigint unsigned NOT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_document_unique` (`loan_application_id`,`document_id`),
  KEY `loan_documents_document_id_foreign` (`document_id`),
  KEY `loan_documents_reviewed_by_foreign` (`reviewed_by`),
  KEY `loan_documents_verified_by_foreign` (`verified_by`),
  CONSTRAINT `loan_documents_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_documents_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_documents_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_documents_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_documents`
--

LOCK TABLES `loan_documents` WRITE;
/*!40000 ALTER TABLE `loan_documents` DISABLE KEYS */;
INSERT INTO `loan_documents` VALUES (1,2,1,NULL,NULL,NULL,NULL,'2026-10-13 03:53:25','2026-10-13 03:53:25'),(2,2,2,NULL,NULL,NULL,NULL,'2026-10-13 03:53:25','2026-10-13 03:53:25'),(3,2,3,NULL,NULL,NULL,NULL,'2026-10-13 03:53:25','2026-10-13 03:53:25'),(4,2,4,NULL,NULL,NULL,NULL,'2026-10-13 03:53:25','2026-10-13 03:53:25'),(5,2,5,NULL,NULL,NULL,NULL,'2026-10-13 03:53:26','2026-10-13 03:53:26'),(6,2,6,NULL,NULL,NULL,NULL,'2026-10-13 03:53:28','2026-10-13 03:53:28'),(7,2,7,NULL,NULL,NULL,NULL,'2026-10-13 03:53:30','2026-10-13 03:53:30'),(8,3,8,NULL,NULL,NULL,NULL,'2026-10-13 05:15:32','2026-10-13 05:15:32'),(9,3,9,NULL,NULL,NULL,NULL,'2026-10-13 05:15:38','2026-10-13 05:15:38'),(10,3,10,NULL,NULL,NULL,NULL,'2026-10-13 05:15:44','2026-10-13 05:15:44'),(11,4,8,1,'2026-10-14 06:38:44',NULL,NULL,'2026-10-14 06:37:33','2026-10-14 06:38:57'),(12,4,11,1,'2026-10-14 06:38:44',NULL,NULL,'2026-10-14 06:37:35','2026-10-14 06:38:57'),(13,4,12,1,'2026-10-14 06:38:44',NULL,NULL,'2026-10-14 06:37:37','2026-10-14 06:38:57'),(14,5,13,1,'2026-10-14 08:16:21',NULL,NULL,'2026-10-14 07:40:56','2026-10-14 08:16:33');
/*!40000 ALTER TABLE `loan_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_installments`
--

DROP TABLE IF EXISTS `loan_installments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_installments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `loan_revision_id` bigint unsigned DEFAULT NULL,
  `installment_no` int unsigned NOT NULL,
  `due_date` date NOT NULL,
  `amount_due` decimal(15,2) NOT NULL,
  `amount_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `penalty_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `penalty_waived_by` bigint unsigned DEFAULT NULL,
  `penalty_waived_reason` text COLLATE utf8mb4_unicode_ci,
  `balance` decimal(15,2) NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'upcoming',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_app_customer_installment_unique` (`loan_application_id`,`customer_id`,`installment_no`),
  KEY `loan_installments_customer_id_foreign` (`customer_id`),
  KEY `loan_installments_loan_revision_id_foreign` (`loan_revision_id`),
  KEY `loan_installments_penalty_waived_by_foreign` (`penalty_waived_by`),
  KEY `loan_installments_status_index` (`status`),
  CONSTRAINT `loan_installments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_installments_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_installments_loan_revision_id_foreign` FOREIGN KEY (`loan_revision_id`) REFERENCES `loan_revisions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_installments_penalty_waived_by_foreign` FOREIGN KEY (`penalty_waived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_installments`
--

LOCK TABLES `loan_installments` WRITE;
/*!40000 ALTER TABLE `loan_installments` DISABLE KEYS */;
INSERT INTO `loan_installments` VALUES (1,4,4,NULL,1,'2026-11-14',27500.00,0.00,0.00,NULL,NULL,27500.00,'upcoming',NULL,'2026-10-14 06:39:49','2026-10-14 06:39:49'),(2,4,4,NULL,2,'2026-12-14',27500.00,0.00,0.00,NULL,NULL,27500.00,'upcoming',NULL,'2026-10-14 06:39:49','2026-10-14 06:39:49'),(3,4,4,NULL,3,'2027-01-14',27500.00,0.00,0.00,NULL,NULL,27500.00,'upcoming',NULL,'2026-10-14 06:39:49','2026-10-14 06:39:49'),(4,4,4,NULL,4,'2027-02-14',27500.00,0.00,0.00,NULL,NULL,27500.00,'upcoming',NULL,'2026-10-14 06:39:49','2026-10-14 06:39:49'),(5,4,4,NULL,5,'2027-03-14',27500.00,0.00,0.00,NULL,NULL,27500.00,'upcoming',NULL,'2026-10-14 06:39:49','2026-10-14 06:39:49'),(6,4,4,NULL,6,'2027-04-14',27500.00,0.00,0.00,NULL,NULL,27500.00,'upcoming',NULL,'2026-10-14 06:39:49','2026-10-14 06:39:49'),(7,4,4,NULL,7,'2027-05-14',27500.00,0.00,0.00,NULL,NULL,27500.00,'upcoming',NULL,'2026-10-14 06:39:49','2026-10-14 06:39:49'),(8,4,4,NULL,8,'2027-06-14',27500.00,0.00,0.00,NULL,NULL,27500.00,'upcoming',NULL,'2026-10-14 06:39:49','2026-10-14 06:39:49'),(9,4,4,NULL,9,'2027-07-14',27500.00,0.00,0.00,NULL,NULL,27500.00,'upcoming',NULL,'2026-10-14 06:39:49','2026-10-14 06:39:49'),(10,4,4,NULL,10,'2027-08-14',27500.00,0.00,0.00,NULL,NULL,27500.00,'upcoming',NULL,'2026-10-14 06:39:49','2026-10-14 06:39:49'),(11,4,4,NULL,11,'2027-09-14',27500.00,0.00,0.00,NULL,NULL,27500.00,'upcoming',NULL,'2026-10-14 06:39:49','2026-10-14 06:39:49'),(12,4,4,NULL,12,'2027-10-14',27500.00,0.00,0.00,NULL,NULL,27500.00,'upcoming',NULL,'2026-10-14 06:39:49','2026-10-14 06:39:49'),(13,5,6,NULL,1,'2026-11-14',36666.67,0.00,0.00,NULL,NULL,36666.67,'upcoming',NULL,'2026-10-14 08:16:49','2026-10-14 08:16:49'),(14,5,6,NULL,2,'2026-12-14',36666.67,0.00,0.00,NULL,NULL,36666.67,'upcoming',NULL,'2026-10-14 08:16:49','2026-10-14 08:16:49'),(15,5,6,NULL,3,'2027-01-14',36666.67,0.00,0.00,NULL,NULL,36666.67,'upcoming',NULL,'2026-10-14 08:16:49','2026-10-14 08:16:49'),(16,5,6,NULL,4,'2027-02-14',36666.67,0.00,0.00,NULL,NULL,36666.67,'upcoming',NULL,'2026-10-14 08:16:49','2026-10-14 08:16:49'),(17,5,6,NULL,5,'2027-03-14',36666.67,0.00,0.00,NULL,NULL,36666.67,'upcoming',NULL,'2026-10-14 08:16:49','2026-10-14 08:16:49'),(18,5,6,NULL,6,'2027-04-14',36666.67,0.00,0.00,NULL,NULL,36666.67,'upcoming',NULL,'2026-10-14 08:16:49','2026-10-14 08:16:49'),(19,5,6,NULL,7,'2027-05-14',36666.67,0.00,0.00,NULL,NULL,36666.67,'upcoming',NULL,'2026-10-14 08:16:49','2026-10-14 08:16:49'),(20,5,6,NULL,8,'2027-06-14',36666.67,0.00,0.00,NULL,NULL,36666.67,'upcoming',NULL,'2026-10-14 08:16:49','2026-10-14 08:16:49'),(21,5,6,NULL,9,'2027-07-14',36666.67,0.00,0.00,NULL,NULL,36666.67,'upcoming',NULL,'2026-10-14 08:16:49','2026-10-14 08:16:49'),(22,5,6,NULL,10,'2027-08-14',36666.67,0.00,0.00,NULL,NULL,36666.67,'upcoming',NULL,'2026-10-14 08:16:49','2026-10-14 08:16:49'),(23,5,6,NULL,11,'2027-09-14',36666.67,0.00,0.00,NULL,NULL,36666.67,'upcoming',NULL,'2026-10-14 08:16:49','2026-10-14 08:16:49'),(24,5,6,NULL,12,'2027-10-14',36666.63,0.00,0.00,NULL,NULL,36666.63,'upcoming',NULL,'2026-10-14 08:16:49','2026-10-14 08:16:49');
/*!40000 ALTER TABLE `loan_installments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_product_legal_documents`
--

DROP TABLE IF EXISTS `loan_product_legal_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_product_legal_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_product_id` bigint unsigned NOT NULL,
  `document_type` enum('direct_loan_agreement','loan_agreement_investment','loan_application_acknowledgement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_product_legal_documents_unique` (`loan_product_id`,`document_type`),
  CONSTRAINT `loan_product_legal_documents_loan_product_id_foreign` FOREIGN KEY (`loan_product_id`) REFERENCES `loan_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_product_legal_documents`
--

LOCK TABLES `loan_product_legal_documents` WRITE;
/*!40000 ALTER TABLE `loan_product_legal_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_product_legal_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_products`
--

DROP TABLE IF EXISTS `loan_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loan_type_id` bigint unsigned DEFAULT NULL,
  `loan_term_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `interest_rate` decimal(6,3) DEFAULT NULL,
  `interest_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'flat',
  `min_amount` decimal(15,2) NOT NULL,
  `max_amount` decimal(15,2) NOT NULL,
  `min_term_months` int unsigned NOT NULL,
  `max_term_months` int unsigned NOT NULL,
  `processing_fee_type` enum('fixed','percentage') COLLATE utf8mb4_unicode_ci NOT NULL,
  `processing_fee_value` decimal(10,2) NOT NULL,
  `penalty_value` decimal(10,2) DEFAULT NULL,
  `grace_period_days` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_islamic` tinyint(1) NOT NULL DEFAULT '0',
  `is_group_loan` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_products_code_unique` (`code`),
  KEY `loan_products_loan_type_id_foreign` (`loan_type_id`),
  KEY `loan_products_loan_term_id_foreign` (`loan_term_id`),
  CONSTRAINT `loan_products_loan_term_id_foreign` FOREIGN KEY (`loan_term_id`) REFERENCES `loan_terms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_products_loan_type_id_foreign` FOREIGN KEY (`loan_type_id`) REFERENCES `loan_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_products`
--

LOCK TABLES `loan_products` WRITE;
/*!40000 ALTER TABLE `loan_products` DISABLE KEYS */;
INSERT INTO `loan_products` VALUES (1,'Personal',NULL,1,1,NULL,10.000,'flat',10000.00,1000000.00,6,60,'fixed',1000.00,1000.00,0,1,0,0,NULL,'2026-10-12 10:53:02','2026-10-12 10:53:02');
/*!40000 ALTER TABLE `loan_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_revisions`
--

DROP TABLE IF EXISTS `loan_revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_revisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned NOT NULL,
  `revision_no` int unsigned NOT NULL,
  `revision_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_approval',
  `previous_outstanding_amount` decimal(15,2) NOT NULL,
  `previous_term` int unsigned NOT NULL,
  `previous_installment_amount` decimal(15,2) NOT NULL,
  `revised_outstanding_amount` decimal(15,2) NOT NULL,
  `revised_term` int unsigned NOT NULL,
  `revised_installment_amount` decimal(15,2) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `document` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `requested_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_app_revision_unique` (`loan_application_id`,`revision_no`),
  KEY `loan_revisions_requested_by_foreign` (`requested_by`),
  KEY `loan_revisions_approved_by_foreign` (`approved_by`),
  KEY `loan_app_revision_status_idx` (`loan_application_id`,`status`),
  KEY `loan_revisions_revision_type_index` (`revision_type`),
  KEY `loan_revisions_status_index` (`status`),
  CONSTRAINT `loan_revisions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loan_revisions_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_revisions_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_revisions`
--

LOCK TABLES `loan_revisions` WRITE;
/*!40000 ALTER TABLE `loan_revisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_revisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_term_loan_type`
--

DROP TABLE IF EXISTS `loan_term_loan_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_term_loan_type` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_term_id` bigint unsigned NOT NULL,
  `loan_type_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_term_loan_type_unique` (`loan_term_id`,`loan_type_id`),
  KEY `loan_term_loan_type_loan_type_id_foreign` (`loan_type_id`),
  CONSTRAINT `loan_term_loan_type_loan_term_id_foreign` FOREIGN KEY (`loan_term_id`) REFERENCES `loan_terms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_term_loan_type_loan_type_id_foreign` FOREIGN KEY (`loan_type_id`) REFERENCES `loan_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_term_loan_type`
--

LOCK TABLES `loan_term_loan_type` WRITE;
/*!40000 ALTER TABLE `loan_term_loan_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_term_loan_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_terms`
--

DROP TABLE IF EXISTS `loan_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_terms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_terms_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_terms`
--

LOCK TABLES `loan_terms` WRITE;
/*!40000 ALTER TABLE `loan_terms` DISABLE KEYS */;
INSERT INTO `loan_terms` VALUES (1,'GENERAL','GENERAL',NULL,1,'2026-10-12 10:51:17','2026-10-12 10:51:17');
/*!40000 ALTER TABLE `loan_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_types`
--

DROP TABLE IF EXISTS `loan_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `loan_term_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_types_code_unique` (`code`),
  KEY `loan_types_loan_term_id_foreign` (`loan_term_id`),
  CONSTRAINT `loan_types_loan_term_id_foreign` FOREIGN KEY (`loan_term_id`) REFERENCES `loan_terms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_types`
--

LOCK TABLES `loan_types` WRITE;
/*!40000 ALTER TABLE `loan_types` DISABLE KEYS */;
INSERT INTO `loan_types` VALUES (1,'STANDARD_BORROWING',1,'Standard Borrowing',NULL,1,'2026-10-12 10:51:40','2026-10-13 03:30:56'),(2,'DEVELOPMENT_FUND',1,'Development Fund',NULL,1,'2026-10-13 03:30:45','2026-10-13 03:30:45');
/*!40000 ALTER TABLE `loan_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_otp_verifications`
--

DROP TABLE IF EXISTS `login_otp_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_otp_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `reference` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `status` enum('approved','verified') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approved',
  `verified_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_otp_verifications_reference_unique` (`reference`),
  KEY `login_otp_verifications_user_id_foreign` (`user_id`),
  CONSTRAINT `login_otp_verifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_otp_verifications`
--

LOCK TABLES `login_otp_verifications` WRITE;
/*!40000 ALTER TABLE `login_otp_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_otp_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2025_10_07_055655_create_personal_access_tokens_table',1),(4,'2025_11_23_175058_create_permission_tables',1),(5,'2026_06_15_114550_create_countries_table',1),(6,'2026_06_15_114551_create_groups_table',1),(7,'2026_06_15_114552_create_provinces_table',1),(8,'2026_06_15_114553_create_zonals_table',1),(9,'2026_06_15_114554_create_regions_table',1),(10,'2026_06_15_114555_create_branches_table',1),(11,'2026_06_15_114556_create_departments_table',1),(12,'2026_06_15_114557_create_designations_table',1),(13,'2026_06_15_114558_create_applications_table',1),(14,'2026_06_15_114558_create_customers_table',1),(15,'2026_06_15_114558_create_employees_table',1),(16,'2026_06_15_114559_create_users_table',1),(17,'2026_06_15_114600_add_customer_recommender_foreign_key',1),(18,'2026_06_15_120000_create_activity_logs_table',1),(19,'2026_07_15_120400_create_customer_bank_details_table',1),(20,'2026_07_15_120500_create_guarantors_table',1),(21,'2026_07_15_120600_create_fixed_assests_table',1),(22,'2026_07_15_120700_create_moving_assests_table',1),(23,'2026_07_16_152000_create_documents_table',1),(24,'2026_07_20_065100_create_loan_types_table',1),(25,'2026_07_20_065200_create_loan_terms_table',1),(26,'2026_07_20_065300_create_loan_term_loan_type_table',1),(27,'2026_07_20_070000_create_loan_products_table',1),(28,'2026_07_20_075000_create_group_loans_table',1),(29,'2026_07_20_075100_create_group_loan_items_table',1),(30,'2026_07_20_080000_create_loan_applications_table',1),(31,'2026_07_20_080100_add_document_loan_application_foreign_key',1),(32,'2026_07_20_090000_create_loan_application_guarantors_table',1),(33,'2026_08_03_100100_create_loan_application_status_history_table',1),(34,'2026_08_03_105000_create_loan_revisions_table',1),(35,'2026_08_03_110000_create_loan_installments_table',1),(36,'2026_08_03_120000_create_payments_table',1),(37,'2026_08_03_130000_create_external_recovery_agents_table',1),(38,'2026_08_03_140000_create_recovery_cases_table',1),(39,'2026_08_03_150000_create_recovery_activities_table',1),(40,'2026_08_03_160000_create_recovery_agents_table',1),(41,'2026_08_03_180000_create_notifications_table',1),(42,'2026_08_07_120100_create_password_change_requests_table',1),(43,'2026_08_12_100100_create_login_otp_verifications_table',1),(44,'2026_08_12_100200_create_liabilities_table',1),(45,'2026_08_13_090000_create_settings_table',1),(46,'2026_08_21_090000_create_admin_dashboard_table',1),(47,'2026_09_02_090100_create_customer_details_table',1),(48,'2026_09_02_090200_create_loan_application_customers_table',1),(49,'2026_09_02_100000_add_loan_term_id_to_loan_types_table',1),(50,'2026_09_03_100000_set_islamic_false_default_on_loan_products',1),(51,'2026_09_09_100000_create_credit_score_events_table',1),(52,'2026_09_09_100100_create_customer_credit_scores_table',1),(53,'2026_09_11_140000_create_legal_document_templates_table',1),(54,'2026_09_11_140100_create_legal_documents_table',1),(55,'2026_09_16_000000_add_member_snapshot_to_loan_application_customers_table',1),(56,'2026_09_16_100000_change_group_loan_items_quantity_to_integer',1),(57,'2026_09_21_120000_create_loan_documents_table',1),(58,'2026_09_28_100000_create_loan_application_securities_table',1),(59,'2026_10_02_110000_create_loan_product_legal_documents_table',1),(60,'2026_10_05_100000_create_lending_mortgages_table',1),(61,'2026_10_08_120000_create_customer_verifications_table',1),(62,'2026_10_09_100000_create_loan_customer_confirmations_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `moving_assests`
--

DROP TABLE IF EXISTS `moving_assests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `moving_assests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `assest_category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `make_model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_of_shares` int DEFAULT NULL,
  `par_value` decimal(15,2) DEFAULT NULL,
  `registation_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `market_value` decimal(15,2) DEFAULT NULL,
  `mortgage_lease_hire_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `moving_assests_slug_unique` (`slug`),
  KEY `moving_assests_customer_id_foreign` (`customer_id`),
  CONSTRAINT `moving_assests_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `moving_assests`
--

LOCK TABLES `moving_assests` WRITE;
/*!40000 ALTER TABLE `moving_assests` DISABLE KEYS */;
INSERT INTO `moving_assests` VALUES (1,'AST-MOV-0001',3,'Vehicle','Karuna',NULL,NULL,0,0.00,'CAE-9089',0.00,'Owned',1,NULL,'2026-10-13 03:41:08','2026-10-13 03:41:08'),(2,'AST-MOV-0002',4,'Vehicle','Mithurjan',NULL,NULL,0,0.00,'CAB-0001',0.00,'Owned',1,NULL,'2026-10-13 05:10:22','2026-10-13 05:10:22');
/*!40000 ALTER TABLE `moving_assests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` enum('email','sms','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error` text COLLATE utf8mb4_unicode_ci,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_loan_application_id_foreign` (`loan_application_id`),
  KEY `notifications_customer_id_foreign` (`customer_id`),
  KEY `notifications_user_id_foreign` (`user_id`),
  KEY `notifications_dedupe_index` (`type`,`channel`,`message_hash`,`created_at`),
  KEY `notifications_type_index` (`type`),
  KEY `notifications_channel_index` (`channel`),
  KEY `notifications_status_index` (`status`),
  CONSTRAINT `notifications_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notifications_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,NULL,1,3,'customer_registration_credentials','email','piranya105@gmail.com','Your CDP Capital Account Credentials','Login credentials email sent to customer.','06305ee400d1b737c1b8d889e0a2582b7f12fb436dac735499a8e8a9c50c7514','sent',NULL,'2026-10-12 10:54:56','2026-10-12 10:54:53','2026-10-12 10:54:56'),(2,1,NULL,1,'application_submitted','system','dev@localhost.com',NULL,'CDP Capital: New loan application pending for review.\nLoan Ref: APP-HCOL-2610120001','aad5314859dff49fe970c83931f55fc6ad610005f4a23d5f8547ed789893f731','sent',NULL,'2026-10-12 10:57:20','2026-10-12 10:57:20','2026-10-12 10:57:20'),(3,NULL,2,4,'customer_registration_credentials','email','piranyacdp@gmail.com','Your CDP Capital Account Credentials','Login credentials email sent to customer.','20888ba6a2a247c8da91cf59b2e736f375f0985f107461419cef5cbf3733927c','sent',NULL,'2026-10-13 03:37:51','2026-10-13 03:37:48','2026-10-13 03:37:51'),(4,NULL,3,5,'customer_registration_credentials','sms','0752932640',NULL,'Login credentials SMS sent to customer.','661275f09fb1d1e66c6394fb6eeaded7c62ee15670f623df6c1f502a84da34cc','pending',NULL,NULL,'2026-10-13 03:41:09','2026-10-13 03:41:09'),(5,2,NULL,1,'application_submitted','system','dev@localhost.com',NULL,'CDP Capital: New loan application pending for review.\nLoan Ref: APP-HCOL-2610130001','62d89e266c93a544c7fda49fc220ea73433c5496e73cb7ba513c825fbd03d74a','sent',NULL,'2026-10-13 03:53:25','2026-10-13 03:53:25','2026-10-13 03:53:25'),(6,NULL,4,6,'customer_registration_credentials','email','cdppiranya@gmail.com','Your CDP Capital Account Credentials','Login credentials email sent to customer.','2b5eba0f7f608918987e43007a5e9737db0fdb486860aadc434927e2bb97edcb','sent',NULL,'2026-10-13 05:10:25','2026-10-13 05:10:23','2026-10-13 05:10:25'),(7,3,NULL,1,'application_submitted','system','dev@localhost.com',NULL,'CDP Capital: New loan application pending for review.\nLoan Ref: APP-HCOL-2610130002','34f633d3119d8945339f8d4baccc324dafe3343b1ecd4f91e32921429b9d581b','sent',NULL,'2026-10-13 05:15:30','2026-10-13 05:15:30','2026-10-13 05:15:30'),(8,NULL,5,7,'customer_registration_credentials','sms','0752932640',NULL,'Login credentials SMS sent to customer.','5f8d0a55c50b632f62f7889067675688e008679a74bc56163553a7554a9bc41d','pending',NULL,NULL,'2026-10-13 06:36:57','2026-10-13 06:36:57'),(9,4,NULL,1,'application_submitted','system','dev@localhost.com',NULL,'CDP Capital: New loan application pending for review.\nLoan Ref: APP-HCOL-2610140001','79f459c1d3cb2a9ca72fa9b1955be7d63a25683c46cc749b4e899f6a732bfb3f','sent',NULL,'2026-10-14 06:37:33','2026-10-14 06:37:33','2026-10-14 06:37:33'),(10,NULL,6,8,'customer_registration_credentials','sms','0752932640',NULL,'Login credentials SMS sent to customer.','b7d3752317dcdf72ab5037072a4c942401633409097274e6b3b46f2cd97d0f34','sent',NULL,'2026-10-14 07:38:59','2026-10-14 07:38:57','2026-10-14 07:38:59'),(11,5,NULL,1,'application_submitted','system','dev@localhost.com',NULL,'CDP Capital: New loan application pending for review.\nLoan Ref: APP-HCOL-2610140002','244d3df265a14ef7ce967965ebc7cdfa77964ce36c3ab201f9a8a3c439e68a44','sent',NULL,'2026-10-14 07:40:54','2026-10-14 07:40:54','2026-10-14 07:40:54');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_change_requests`
--

DROP TABLE IF EXISTS `password_change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_change_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned DEFAULT NULL,
  `otp` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected','verified') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `password_change_requests_user_id_foreign` (`user_id`),
  KEY `password_change_requests_employee_id_foreign` (`employee_id`),
  CONSTRAINT `password_change_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `password_change_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_change_requests`
--

LOCK TABLES `password_change_requests` WRITE;
/*!40000 ALTER TABLE `password_change_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_change_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned NOT NULL,
  `loan_installment_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `receipt_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','cheque','online') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `received_by` bigint unsigned DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `carry_forward_breakdown` json DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_receipt_no_unique` (`receipt_no`),
  KEY `payments_loan_installment_id_foreign` (`loan_installment_id`),
  KEY `payments_customer_id_foreign` (`customer_id`),
  KEY `payments_received_by_foreign` (`received_by`),
  KEY `payments_loan_application_id_paid_at_index` (`loan_application_id`,`paid_at`),
  CONSTRAINT `payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_loan_installment_id_foreign` FOREIGN KEY (`loan_installment_id`) REFERENCES `loan_installments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'Activity Management Permissions','Activity Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(2,'Activity Management Permissions','Activity Show','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(3,'Access Management Permissions','Permission Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(4,'Access Management Permissions','Permission Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(5,'Access Management Permissions','Permission Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(6,'Access Management Permissions','Permission Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(7,'Access Management Permissions','Role Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(8,'Access Management Permissions','Role Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(9,'Access Management Permissions','Role Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(10,'Access Management Permissions','Role Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(11,'User Management Permissions','User Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(12,'User Management Permissions','User Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(13,'User Management Permissions','User Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(14,'User Management Permissions','User Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(15,'User Management Permissions','User Toggle Status','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(16,'Employee Management Permissions','Employee Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(17,'Country Management Permissions','Country Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(18,'Country Management Permissions','Country Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(19,'Country Management Permissions','Country Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(20,'Country Management Permissions','Country Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(21,'Country Management Permissions','Country Toggle Status','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(22,'Province Management Permissions','Province Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(23,'Province Management Permissions','Province Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(24,'Province Management Permissions','Province Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(25,'Province Management Permissions','Province Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(26,'Province Management Permissions','Province Toggle Status','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(27,'Zonal Management Permissions','Zonal Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(28,'Zonal Management Permissions','Zonal Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(29,'Zonal Management Permissions','Zonal Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(30,'Zonal Management Permissions','Zonal Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(31,'Zonal Management Permissions','Zonal Toggle Status','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(32,'Region Management Permissions','Region Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(33,'Region Management Permissions','Region Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(34,'Region Management Permissions','Region Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(35,'Region Management Permissions','Region Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(36,'Region Management Permissions','Region Toggle Status','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(37,'Branch Management Permissions','Branch Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(38,'Branch Management Permissions','Branch Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(39,'Branch Management Permissions','Branch Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(40,'Branch Management Permissions','Branch Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(41,'Branch Management Permissions','Branch Toggle Status','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(42,'Department Management Permissions','Department Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(43,'Department Management Permissions','Department Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(44,'Department Management Permissions','Department Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(45,'Department Management Permissions','Department Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(46,'Department Management Permissions','Department Toggle Status','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(47,'Designation Management Permissions','Designation Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(48,'Designation Management Permissions','Designation Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(49,'Designation Management Permissions','Designation Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(50,'Designation Management Permissions','Designation Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(51,'Designation Management Permissions','Designation Toggle Status','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(52,'Group Management Permissions','Group Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(53,'Group Management Permissions','Group Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(54,'Group Management Permissions','Group Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(55,'Group Management Permissions','Group Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(56,'Group Management Permissions','Group Toggle Status','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(57,'Customer Management Permissions','Customer Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(58,'Customer Management Permissions','Customer Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(59,'Customer Management Permissions','Customer Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(60,'Customer Management Permissions','Customer Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(61,'Customer Management Permissions','Customer Restore','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(62,'Customer Management Permissions','Customer Force Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(63,'Customer Management Permissions','Customer Toggle Status','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(64,'Customer Management Permissions','Customer Financials Live Loan Override','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(65,'Customer Bank Detail Management Permissions','Customer Bank Detail Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(66,'Customer Bank Detail Management Permissions','Customer Bank Detail Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(67,'Customer Bank Detail Management Permissions','Customer Bank Detail Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(68,'Customer Bank Detail Management Permissions','Customer Bank Detail Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(69,'Customer Bank Detail Management Permissions','Customer Bank Detail Toggle Status','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(70,'Guarantor Management Permissions','Guarantor Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(71,'Guarantor Management Permissions','Guarantor Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(72,'Guarantor Management Permissions','Guarantor Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(73,'Guarantor Management Permissions','Guarantor Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(74,'Application Management Permissions','Application Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(75,'Application Management Permissions','Application Create','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(76,'Application Management Permissions','Application Update','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(77,'Application Management Permissions','Application Delete','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(78,'Fixed Asset Management Permissions','Fixed Asset Index','api','2026-10-12 10:46:00','2026-10-12 10:46:00'),(79,'Fixed Asset Management Permissions','Fixed Asset Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(80,'Fixed Asset Management Permissions','Fixed Asset Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(81,'Fixed Asset Management Permissions','Fixed Asset Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(82,'Moving Asset Management Permissions','Moving Asset Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(83,'Moving Asset Management Permissions','Moving Asset Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(84,'Moving Asset Management Permissions','Moving Asset Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(85,'Moving Asset Management Permissions','Moving Asset Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(86,'Liability Management Permissions','Liability Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(87,'Liability Management Permissions','Liability Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(88,'Liability Management Permissions','Liability Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(89,'Liability Management Permissions','Liability Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(90,'Liability Management Permissions','Liability Toggle Status','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(91,'Document Management Permissions','Document Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(92,'Document Management Permissions','Document Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(93,'Document Management Permissions','Document Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(94,'Document Management Permissions','Document Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(95,'Loan Term Management Permissions','Loan Term Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(96,'Loan Term Management Permissions','Loan Term Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(97,'Loan Term Management Permissions','Loan Term Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(98,'Loan Term Management Permissions','Loan Term Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(99,'Loan Term Management Permissions','Loan Term Toggle Status','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(100,'Loan Type Management Permissions','Loan Type Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(101,'Loan Type Management Permissions','Loan Type Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(102,'Loan Type Management Permissions','Loan Type Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(103,'Loan Type Management Permissions','Loan Type Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(104,'Loan Type Management Permissions','Loan Type Toggle Status','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(105,'Loan Product Management Permissions','Loan Product Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(106,'Loan Product Management Permissions','Loan Product Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(107,'Loan Product Management Permissions','Loan Product Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(108,'Loan Product Management Permissions','Loan Product Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(109,'Loan Product Management Permissions','Loan Product Toggle Status','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(110,'Global Search Permissions','Global Search','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(111,'CDP Connect Permissions','CDP Customer Verification','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(112,'Loan Application Management Permissions','Loan Application Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(113,'Loan Application Management Permissions','Loan Application Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(114,'Loan Application Management Permissions','Loan Application Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(115,'Loan Application Management Permissions','Loan Application Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(116,'Loan Application Management Permissions','Loan Application Toggle Status','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(117,'Loan Application Management Permissions','Loan Application Review','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(118,'Loan Application Management Permissions','Loan Application Resubmit','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(119,'Loan Application Management Permissions','Loan Application Verify','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(120,'Loan Application Management Permissions','Loan Application Reverify','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(121,'Loan Application Management Permissions','Loan Application Approve','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(122,'Loan Application Management Permissions','Loan Application Reject','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(123,'Loan Application Management Permissions','Loan Application Reopen','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(124,'Loan Application Management Permissions','Loan Application Offer Response','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(125,'Loan Application Management Permissions','Loan Application Status History Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(126,'Legal Management Permissions','Legal Template Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(127,'Legal Management Permissions','Legal Template Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(128,'Legal Management Permissions','Legal Template Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(129,'Legal Management Permissions','Legal Template Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(130,'Legal Management Permissions','Legal Template Toggle Status','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(131,'Legal Management Permissions','Legal Document Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(132,'Legal Management Permissions','Legal Document Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(133,'Legal Management Permissions','Legal Document Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(134,'Legal Management Permissions','Legal Document Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(135,'Legal Management Permissions','Legal Document Toggle Status','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(136,'Loan Application Management Permissions','Loan Application Disburse','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(137,'Loan Application Management Permissions','Loan Application Cancel','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(138,'Group Loan Management Permissions','Group Loan Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(139,'Group Loan Management Permissions','Group Loan Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(140,'Group Loan Management Permissions','Group Loan Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(141,'Group Loan Management Permissions','Group Loan Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(142,'Group Loan Management Permissions','Group Loan Toggle Status','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(143,'Group Loan Management Permissions','Group Loan Review','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(144,'Group Loan Management Permissions','Group Loan Resubmit','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(145,'Group Loan Management Permissions','Group Loan Verify','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(146,'Group Loan Management Permissions','Group Loan Reverify','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(147,'Group Loan Management Permissions','Group Loan Approve','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(148,'Group Loan Management Permissions','Group Loan Reject','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(149,'Group Loan Management Permissions','Group Loan Reopen','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(150,'Group Loan Management Permissions','Group Loan Offer Response','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(151,'Group Loan Management Permissions','Group Loan Disburse','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(152,'Group Loan Management Permissions','Group Loan Cancel','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(153,'Group Loan Item Management Permissions','Group Loan Item Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(154,'Group Loan Item Management Permissions','Group Loan Item Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(155,'Group Loan Item Management Permissions','Group Loan Item Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(156,'Loan Revision Management Permissions','Loan Revision Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(157,'Loan Revision Management Permissions','Loan Revision Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(158,'Loan Revision Management Permissions','Loan Revision Approve','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(159,'Loan Revision Management Permissions','Loan Revision Reject','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(160,'Loan Revision Management Permissions','Loan Revision Cancel','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(161,'Loan Application Guarantor Management Permissions','Loan Application Guarantor Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(162,'Loan Application Guarantor Management Permissions','Loan Application Guarantor Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(163,'Loan Application Guarantor Management Permissions','Loan Application Guarantor Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(164,'Loan Application Guarantor Management Permissions','Loan Application Guarantor Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(165,'Loan Application Customer Management Permissions','Loan Application Customer Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(166,'Loan Application Customer Management Permissions','Loan Application Customer Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(167,'Loan Application Customer Management Permissions','Loan Application Customer Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(168,'Loan Application Customer Management Permissions','Loan Application Customer Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(169,'Loan Installment Management Permissions','Loan Installment Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(170,'Loan Installment Management Permissions','Loan Installment Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(171,'Loan Installment Management Permissions','Loan Installment Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(172,'Loan Installment Management Permissions','Loan Installment Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(173,'Payment Management Permissions','Payment Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(174,'Payment Management Permissions','Payment Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(175,'Payment Management Permissions','Payment Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(176,'Payment Management Permissions','Payment Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(177,'Recovery Case Management Permissions','Recovery Case Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(178,'Recovery Case Management Permissions','Recovery Case Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(179,'Recovery Case Management Permissions','Recovery Case Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(180,'Recovery Case Management Permissions','Recovery Case Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(181,'Recovery Activity Management Permissions','Recovery Activity Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(182,'Recovery Activity Management Permissions','Recovery Activity Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(183,'Recovery Activity Management Permissions','Recovery Activity Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(184,'Recovery Activity Management Permissions','Recovery Activity Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(185,'Recovery Agent Management Permissions','Recovery Agent Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(186,'Recovery Agent Management Permissions','Recovery Agent Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(187,'Recovery Agent Management Permissions','Recovery Agent Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(188,'Recovery Agent Management Permissions','Recovery Agent Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(189,'External Recovery Agent Management Permissions','External Recovery Agent Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(190,'External Recovery Agent Management Permissions','External Recovery Agent Create','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(191,'External Recovery Agent Management Permissions','External Recovery Agent Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(192,'External Recovery Agent Management Permissions','External Recovery Agent Delete','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(193,'Notification Management Permissions','Notification Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(194,'System Settings Permissions','Setting Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(195,'System Settings Permissions','Setting Update','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(196,'Loan Security Permissions','use_plan_1','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(197,'Loan Security Permissions','use_plan_2','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(198,'Loan Security Permissions','use_plan_3','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(199,'Loan Security Permissions','use_cdp_inv_001','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(200,'Loan Security Permissions','use_cdp_pro_001','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(201,'Loan Security Permissions','use_cdp_vec_001','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(202,'Credit Score Management Permissions','Credit Score Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(203,'Credit Score Management Permissions','Credit Score Recompute','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(204,'Admin Dashboard Management Permissions','Admin Dashboard Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01'),(205,'Report Management Permissions','Report Index','api','2026-10-12 10:46:01','2026-10-12 10:46:01');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provinces`
--

DROP TABLE IF EXISTS `provinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provinces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provinces_code_unique` (`code`),
  KEY `provinces_country_id_foreign` (`country_id`),
  CONSTRAINT `provinces_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provinces`
--

LOCK TABLES `provinces` WRITE;
/*!40000 ALTER TABLE `provinces` DISABLE KEYS */;
INSERT INTO `provinces` VALUES (1,'Western','WP',1,1,'2026-10-12 10:49:11','2026-10-12 10:49:11');
/*!40000 ALTER TABLE `provinces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recovery_activities`
--

DROP TABLE IF EXISTS `recovery_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recovery_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recovery_case_id` bigint unsigned NOT NULL,
  `activity_type` enum('call','visit','payment_promise','collection_attempt') COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `promised_amount` decimal(15,2) DEFAULT NULL,
  `promised_date` date DEFAULT NULL,
  `performed_by` bigint unsigned DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recovery_activities_recovery_case_id_foreign` (`recovery_case_id`),
  KEY `recovery_activities_performed_by_foreign` (`performed_by`),
  KEY `recovery_activities_activity_type_index` (`activity_type`),
  CONSTRAINT `recovery_activities_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recovery_activities_recovery_case_id_foreign` FOREIGN KEY (`recovery_case_id`) REFERENCES `recovery_cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recovery_activities`
--

LOCK TABLES `recovery_activities` WRITE;
/*!40000 ALTER TABLE `recovery_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `recovery_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recovery_agents`
--

DROP TABLE IF EXISTS `recovery_agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recovery_agents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recovery_agents_user_id_unique` (`user_id`),
  KEY `recovery_agents_branch_id_foreign` (`branch_id`),
  CONSTRAINT `recovery_agents_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recovery_agents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recovery_agents`
--

LOCK TABLES `recovery_agents` WRITE;
/*!40000 ALTER TABLE `recovery_agents` DISABLE KEYS */;
/*!40000 ALTER TABLE `recovery_agents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recovery_cases`
--

DROP TABLE IF EXISTS `recovery_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recovery_cases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `case_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','in_progress','resolved','escalated','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `stage` enum('internal','external') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal',
  `overdue_amount` decimal(15,2) DEFAULT NULL,
  `assigned_agent_id` bigint unsigned DEFAULT NULL,
  `external_agent_id` bigint unsigned DEFAULT NULL,
  `parent_case_id` bigint unsigned DEFAULT NULL,
  `opened_by` bigint unsigned DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `opened_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recovery_cases_case_no_unique` (`case_no`),
  KEY `recovery_cases_loan_application_id_foreign` (`loan_application_id`),
  KEY `recovery_cases_customer_id_foreign` (`customer_id`),
  KEY `recovery_cases_assigned_agent_id_foreign` (`assigned_agent_id`),
  KEY `recovery_cases_external_agent_id_foreign` (`external_agent_id`),
  KEY `recovery_cases_parent_case_id_foreign` (`parent_case_id`),
  KEY `recovery_cases_opened_by_foreign` (`opened_by`),
  KEY `recovery_cases_status_index` (`status`),
  CONSTRAINT `recovery_cases_assigned_agent_id_foreign` FOREIGN KEY (`assigned_agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recovery_cases_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recovery_cases_external_agent_id_foreign` FOREIGN KEY (`external_agent_id`) REFERENCES `external_recovery_agents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recovery_cases_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recovery_cases_opened_by_foreign` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recovery_cases_parent_case_id_foreign` FOREIGN KEY (`parent_case_id`) REFERENCES `recovery_cases` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recovery_cases`
--

LOCK TABLES `recovery_cases` WRITE;
/*!40000 ALTER TABLE `recovery_cases` DISABLE KEYS */;
/*!40000 ALTER TABLE `recovery_cases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regions`
--

DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zonal_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `regions_code_unique` (`code`),
  KEY `regions_zonal_id_foreign` (`zonal_id`),
  CONSTRAINT `regions_zonal_id_foreign` FOREIGN KEY (`zonal_id`) REFERENCES `zonals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regions`
--

LOCK TABLES `regions` WRITE;
/*!40000 ALTER TABLE `regions` DISABLE KEYS */;
INSERT INTO `regions` VALUES (1,'Colombo','COL',1,1,'2026-10-12 10:49:50','2026-10-12 10:49:50');
/*!40000 ALTER TABLE `regions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(39,1),(40,1),(41,1),(42,1),(43,1),(44,1),(45,1),(46,1),(47,1),(48,1),(49,1),(50,1),(51,1),(52,1),(53,1),(54,1),(55,1),(56,1),(57,1),(58,1),(59,1),(60,1),(61,1),(62,1),(63,1),(64,1),(65,1),(66,1),(67,1),(68,1),(69,1),(70,1),(71,1),(72,1),(73,1),(74,1),(75,1),(76,1),(77,1),(78,1),(79,1),(80,1),(81,1),(82,1),(83,1),(84,1),(85,1),(86,1),(87,1),(88,1),(89,1),(90,1),(91,1),(92,1),(93,1),(94,1),(95,1),(96,1),(97,1),(98,1),(99,1),(100,1),(101,1),(102,1),(103,1),(104,1),(105,1),(106,1),(107,1),(108,1),(109,1),(110,1),(111,1),(112,1),(113,1),(114,1),(115,1),(116,1),(117,1),(118,1),(119,1),(120,1),(121,1),(122,1),(123,1),(124,1),(125,1),(126,1),(127,1),(128,1),(129,1),(130,1),(131,1),(132,1),(133,1),(134,1),(135,1),(136,1),(137,1),(138,1),(139,1),(140,1),(141,1),(142,1),(143,1),(144,1),(145,1),(146,1),(147,1),(148,1),(149,1),(150,1),(151,1),(152,1),(153,1),(154,1),(155,1),(156,1),(157,1),(158,1),(159,1),(160,1),(161,1),(162,1),(163,1),(164,1),(165,1),(166,1),(167,1),(168,1),(169,1),(170,1),(171,1),(172,1),(173,1),(174,1),(175,1),(176,1),(177,1),(178,1),(179,1),(180,1),(181,1),(182,1),(183,1),(184,1),(185,1),(186,1),(187,1),(188,1),(189,1),(190,1),(191,1),(192,1),(193,1),(194,1),(195,1),(196,1),(197,1),(198,1),(199,1),(200,1),(201,1),(202,1),(203,1),(204,1),(205,1),(193,2),(204,2);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_protected` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','api',0,'2026-10-12 10:46:01','2026-10-12 10:46:01'),(2,'Employee','api',0,'2026-10-12 10:46:01','2026-10-12 10:46:01');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'installment_due_period_days','30','integer','loan_recovery','Days after an installment due date before it is marked overdue (used when the loan product has no grace_period_days set).','2026-10-12 10:45:57','2026-10-12 10:45:57'),(2,'overdue_sms_frequency_days','7','integer','loan_recovery','How often (in days) to send an SMS reminder to a customer with an overdue installment.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(3,'overdue_sms_duration_weeks','3','integer','loan_recovery','How many weeks after becoming overdue to keep sending overdue SMS reminders.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(4,'internal_recovery_threshold_days','30','integer','loan_recovery','Days overdue at which a loan application is automatically escalated to internal recovery.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(5,'external_recovery_threshold_days','45','integer','loan_recovery','Days overdue at which an internal recovery case is automatically escalated to external recovery.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(6,'sms_notifications_enabled','1','boolean','loan_recovery','Master switch for overdue SMS notifications.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(7,'recovery_escalation_enabled','1','boolean','loan_recovery','Master switch for automatic recovery case escalation (internal/external).','2026-10-12 10:45:57','2026-10-12 10:45:57'),(8,'customer_bank_list','[\"Commercial Bank\",\"DFCC Bank\",\"HNB\",\"HDFC Bank\",\"NSB\",\"NTB\",\"NDB\",\"Pan Asia Bank\",\"People\'s Bank\",\"Sampath Bank\"]','json','customer','Bank names offered in the customer bank details dropdown.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(9,'loan_revision_enabled','1','boolean','loan_revision','Master switch for the loan revision (restructuring) feature.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(10,'loan_revision_allowed_types','[\"reduce_installment\",\"extend_term\",\"principal_only\"]','json','loan_revision','Revision types an officer may create for a loan application.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(11,'group_loan_competency','[\"Entrepreneurship\"]','json','group_loan','Competency options offered for a Group Loan application\'s competency field — the submitted value must match one of these.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(12,'group_loan_service_charge_percentage','10','decimal','group_loan','Service charge percentage applied to a Group Loan\'s principal in place of interest, snapshotted onto the application at submission time.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(13,'loan_approval_segregation_enabled','1','boolean','loan_approval','Require review, verify and approve to be performed by three different users. Turn off only where one officer legitimately handles the whole file (a very small branch), since it removes the maker-checker control.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(14,'credit_score_enabled','1','boolean','credit_score','Master switch for repayment credit scoring. Turning it off stops all recomputation; scores already stored are left untouched rather than wiped, so switching it back on resumes from where it stopped.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(15,'credit_score_on_time_points','10','integer','credit_score','Points added to the score for each installment settled on or before its due date (plus the credit score grace days).','2026-10-12 10:45:57','2026-10-12 10:45:57'),(16,'credit_score_late_penalty_points','10','integer','credit_score','Points taken off the score for each installment settled late, or still unpaid past its grace period. Enter a positive number; it is subtracted. Two days late and ninety days late cost the same.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(17,'credit_score_grace_days','0','integer','credit_score','Days after the due date a payment may still arrive and count as on time. Separate from the recovery grace period, which decides when a loan turns overdue and is charged a penalty.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(18,'max_loans_per_guarantor','1','integer','guarantor','How many live loans one person may stand guarantor for. Counted by ID number across every customer, so the same person is one guarantor no matter how many times they have been entered. Loans that are rejected, cancelled or closed release their guarantors and stop counting. Set 0 for no limit.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(19,'credit_score_count_unpaid_overdue','1','boolean','credit_score','Deduct points as soon as an installment passes its grace period unpaid, instead of waiting until it is eventually paid. Turn this off and a borrower who simply never pays would never lose a point.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(20,'cdp_investment_max_loan_percentage','50','decimal','loan_security','The most a loan secured by a CDP Investment can be, as a percentage of the investment value. The percentage configured here is applied to the value CDP Core reports for the policy. Several investments may be pledged to one loan; their ceilings are added together.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(21,'property_mortgage_max_loan_percentage','70','decimal','loan_security','The most a loan secured by a Property Mortgage can be, as a percentage of the property valuation. At 70, a property valued at 10,000,000 secures a loan of up to 7,000,000. Several properties may be pledged to one loan; their ceilings are added together.','2026-10-12 10:45:57','2026-10-12 10:45:57'),(22,'vehicle_max_loan_percentage','70','decimal','loan_security','The most a loan secured by a Vehicle can be, as a percentage of the vehicle value. At 70, a vehicle valued at 3,000,000 secures a loan of up to 2,100,000. Several vehicles may be pledged to one loan; their ceilings are added together.','2026-10-12 10:45:57','2026-10-12 10:45:57');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `password_expires_at` timestamp NULL DEFAULT NULL,
  `password_locked_at` timestamp NULL DEFAULT NULL,
  `two_factor_verified_at` timestamp NULL DEFAULT NULL,
  `user_type` enum('admin','staff','customer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `employee_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `can_login` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_employee_id_foreign` (`employee_id`),
  KEY `users_customer_id_foreign` (`customer_id`),
  CONSTRAINT `users_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Development Admin','devadmin','dev@localhost.com','$2y$12$zQtEDp8CmpL/BwkkZLxeW.YDEn8.Sy2NX2je/Er20UiOZV74ahkxm','2026-10-12 11:08:38',NULL,NULL,NULL,'admin',NULL,NULL,1,1,'2026-10-14 04:38:08','127.0.0.1',NULL,'2026-10-12 10:46:01','2026-10-14 04:38:08'),(2,'System','system',NULL,'$2y$12$FPaW2kjTHZNTz7ZzJM42t.KHzvfG5Rl9tWtY5sIcSIgN3C3uhJ1GG',NULL,NULL,NULL,NULL,'admin',NULL,NULL,0,0,NULL,NULL,NULL,'2026-10-12 10:46:01','2026-10-12 10:46:01'),(3,'Piranya','Piranya','piranya105@gmail.com','$2y$12$HIkz6z0kTdB36FoyJAE4zO.Yz62gGC2SOzYLthnhxaic3rYpIkIhK',NULL,'2026-10-15 10:54:53',NULL,NULL,'customer',NULL,1,1,1,NULL,NULL,NULL,'2026-10-12 10:54:53','2026-10-12 10:54:53'),(4,'Nilukshi','Niulkshiya','piranyacdp@gmail.com','$2y$12$R0LHxcqJVz4YaMJVy.WIpOH.EIUyOY31hTT6WAqs4PIxBOU8VJvf2',NULL,'2026-10-16 03:37:48',NULL,NULL,'customer',NULL,2,1,1,NULL,NULL,NULL,'2026-10-13 03:37:48','2026-10-13 03:37:48'),(5,'Karuna','Karuna',NULL,'$2y$12$FsvatA36EyFQbDs2QjWxfeK6TgvEUoFlNw41OpiPxEVplBHrj3.KO',NULL,'2026-10-16 03:41:09',NULL,NULL,'customer',NULL,3,1,1,NULL,NULL,NULL,'2026-10-13 03:41:09','2026-10-13 03:41:09'),(6,'Mithurjan','Mithurjan','cdppiranya@gmail.com','$2y$12$6LnicDC6TzfxsqQP.8pYlOLyh2iqQepZrCOdWW9HsEYrP6iqChCya',NULL,'2026-10-16 05:10:23',NULL,NULL,'customer',NULL,4,1,1,NULL,NULL,NULL,'2026-10-13 05:10:23','2026-10-13 05:10:23'),(7,'Mithu','CUS0005',NULL,'$2y$12$tv9TSsZfsNWsSE64hNdpuuRTchc5rc5Lkrn0Uay3b/hvwntajiR6K',NULL,'2026-10-16 06:36:57',NULL,NULL,'customer',NULL,5,1,1,NULL,NULL,NULL,'2026-10-13 06:36:57','2026-10-13 06:36:57'),(8,'Karinath','Kari',NULL,'$2y$12$AE09IzrwPxh9lHwqduKW3eklsu.kM/ZOfF74x66qW65mL52oAFwGu',NULL,'2026-10-17 07:38:57',NULL,NULL,'customer',NULL,6,1,1,NULL,NULL,NULL,'2026-10-14 07:38:57','2026-10-14 07:38:57');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zonals`
--

DROP TABLE IF EXISTS `zonals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `zonals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `province_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zonals_code_unique` (`code`),
  KEY `zonals_province_id_foreign` (`province_id`),
  CONSTRAINT `zonals_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zonals`
--

LOCK TABLES `zonals` WRITE;
/*!40000 ALTER TABLE `zonals` DISABLE KEYS */;
INSERT INTO `zonals` VALUES (1,'Western','W',1,1,'2026-10-12 10:49:31','2026-10-12 10:49:31');
/*!40000 ALTER TABLE `zonals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'cdp_credix_api'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-14 14:18:19
