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
) ENGINE=InnoDB AUTO_INCREMENT=1242 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-25&start_date=2026-09-01','GET','2026-09-25 06:30:17','2026-09-25 06:30:17'),(2,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 06:30:45','2026-09-25 06:30:45'),(3,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-25&start_date=2026-09-01','GET','2026-09-25 06:30:46','2026-09-25 06:30:46'),(4,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 06:30:47','2026-09-25 06:30:47'),(5,1,'INDEX','Customer','Customers index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 06:31:07','2026-09-25 06:31:07'),(6,1,'INDEX','Customer','Customers index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 06:31:11','2026-09-25 06:31:11'),(7,1,'EMAIL_SENT','Customer','Registration credentials email sent to customer: sulanihost@gmail.com','{\"email\": \"sulanihost@gmail.com\", \"customer_id\": 1, \"notification_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-09-25 06:34:55','2026-09-25 06:34:55'),(8,1,'CREATE','Customer','Customer created with associated details','{\"creator_id\": 1, \"customer_code\": \"CUS0001\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-09-25 06:34:55','2026-09-25 06:34:55'),(9,1,'CREATE','User','Login account created for customer: CUS0001','{\"user_id\": 3, \"username\": \"Sulani\", \"customer_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-09-25 06:34:55','2026-09-25 06:34:55'),(10,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 06:34:58','2026-09-25 06:34:58'),(11,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 06:34:59','2026-09-25 06:34:59'),(12,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 06:35:11','2026-09-25 06:35:11'),(13,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 06:35:12','2026-09-25 06:35:12'),(14,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-09-25 06:35:53','2026-09-25 06:35:53'),(15,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-09-25 06:36:01','2026-09-25 06:36:01'),(16,1,'CREATE','LoanTerm','Created loan term: Islamic','{\"code\": \"ISLAMIC\", \"title\": \"Islamic\", \"is_active\": true, \"description\": null}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-terms','POST','2026-09-25 06:36:18','2026-09-25 06:36:18'),(17,1,'UPDATE','LoanTerm','Updated loan term: Islamic','{\"code\": \"ISLAMIC\", \"title\": \"Islamic\", \"is_active\": true, \"description\": \"Islamic Loans\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-terms/2','PUT','2026-09-25 06:36:40','2026-09-25 06:36:40'),(18,1,'CREATE','LoanType','Created loan type: Development Fund','{\"code\": \"DEVELOPMENT_FUND\", \"title\": \"Development Fund\", \"is_active\": true, \"loan_term_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-types','POST','2026-09-25 06:37:38','2026-09-25 06:37:38'),(19,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?page=1','GET','2026-09-25 06:37:50','2026-09-25 06:37:50'),(20,1,'CREATE','LoanProduct','Created loan product: Business Loan','{\"name\": \"Business Loan\", \"is_active\": true, \"is_islamic\": false, \"max_amount\": 500000, \"min_amount\": 10000, \"loan_term_id\": 1, \"loan_type_id\": 1, \"interest_rate\": 10, \"interest_type\": \"flat\", \"is_group_loan\": false, \"penalty_value\": 1000, \"max_term_months\": 60, \"min_term_months\": 6, \"grace_period_days\": 0, \"processing_fee_type\": \"fixed\", \"processing_fee_value\": 1000}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products','POST','2026-09-25 06:39:02','2026-09-25 06:39:02'),(21,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?page=1','GET','2026-09-25 06:39:10','2026-09-25 06:39:10'),(22,1,'CREATE','LoanProduct','Created loan product: Mortgage Loan','{\"code\": \"PLN-003\", \"name\": \"Mortgage Loan\", \"is_active\": true, \"is_islamic\": false, \"max_amount\": 500000, \"min_amount\": 10000, \"loan_term_id\": 1, \"loan_type_id\": 1, \"interest_rate\": 10, \"interest_type\": \"flat\", \"is_group_loan\": false, \"penalty_value\": 1000, \"max_term_months\": 60, \"min_term_months\": 6, \"grace_period_days\": 0, \"processing_fee_type\": \"fixed\", \"processing_fee_value\": 1000}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products','POST','2026-09-25 06:40:13','2026-09-25 06:40:13'),(23,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?page=1','GET','2026-09-25 06:40:20','2026-09-25 06:40:20'),(24,1,'UPDATE','LoanProduct','Updated loan product: Business Loan','{\"code\": \"PLN-002\", \"name\": \"Business Loan\", \"is_active\": true, \"is_islamic\": false, \"max_amount\": 50000000, \"min_amount\": 1000000, \"loan_term_id\": 1, \"loan_type_id\": 1, \"interest_rate\": 10, \"interest_type\": \"flat\", \"is_group_loan\": false, \"penalty_value\": 1000, \"max_term_months\": 60, \"min_term_months\": 6, \"grace_period_days\": 0, \"processing_fee_type\": \"fixed\", \"processing_fee_value\": 100000}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products/2','PUT','2026-09-25 06:40:47','2026-09-25 06:40:47'),(25,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?page=1','GET','2026-09-25 06:40:56','2026-09-25 06:40:56'),(26,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 06:57:46','2026-09-25 06:57:46'),(27,1,'INDEX','Customer','Customers index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 06:57:47','2026-09-25 06:57:47'),(28,1,'EMAIL_SENT','Customer','Registration credentials email sent to customer: piranya@gmail.com','{\"email\": \"piranya@gmail.com\", \"customer_id\": 2, \"notification_id\": 2}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-09-25 07:00:50','2026-09-25 07:00:50'),(29,1,'CREATE','Customer','Customer created with associated details','{\"creator_id\": 1, \"customer_code\": \"CUS0002\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-09-25 07:00:50','2026-09-25 07:00:50'),(30,1,'CREATE','User','Login account created for customer: CUS0002','{\"user_id\": 4, \"username\": \"Piranya\", \"customer_id\": 2}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-09-25 07:00:50','2026-09-25 07:00:50'),(31,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 07:00:52','2026-09-25 07:00:52'),(32,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 07:00:54','2026-09-25 07:00:54'),(33,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 07:05:44','2026-09-25 07:05:44'),(34,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 07:05:47','2026-09-25 07:05:47'),(35,1,'INDEX','GroupLoan','Group loans index accessed','{\"count\": 0, \"filters\": {\"status\": \"available\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/group-loans/status/available?page=1&per_page=15','GET','2026-09-25 07:06:17','2026-09-25 07:06:17'),(36,1,'INDEX','GroupLoan','Group loans index accessed','{\"count\": 0, \"filters\": {\"status\": \"available\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/group-loans/status/available?page=1&per_page=15','GET','2026-09-25 07:06:19','2026-09-25 07:06:19'),(37,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-09-25 07:06:34','2026-09-25 07:06:34'),(38,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-09-25 07:06:35','2026-09-25 07:06:35'),(39,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-09-25 07:06:38','2026-09-25 07:06:38'),(40,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-09-25 07:06:40','2026-09-25 07:06:40'),(41,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 07:16:47','2026-09-25 07:16:47'),(42,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-25&start_date=2026-09-01','GET','2026-09-25 07:16:47','2026-09-25 07:16:47'),(43,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 07:16:48','2026-09-25 07:16:48'),(44,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-09-25 07:16:51','2026-09-25 07:16:51'),(45,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-09-25 07:16:51','2026-09-25 07:16:51'),(46,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-09-25 07:16:52','2026-09-25 07:16:52'),(47,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-09-25 07:16:53','2026-09-25 07:16:53'),(48,1,'INDEX','LoanRevision','Loan revisions index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-revisions?page=1','GET','2026-09-25 07:16:56','2026-09-25 07:16:56'),(49,1,'INDEX','LoanRevision','Loan revisions index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-revisions?page=1','GET','2026-09-25 07:16:57','2026-09-25 07:16:57'),(50,1,'INDEX','RecoveryAgent','Recovery agents index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-agents?page=1','GET','2026-09-25 07:24:14','2026-09-25 07:24:14'),(51,1,'INDEX','RecoveryAgent','Recovery agents index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-agents?page=1','GET','2026-09-25 07:24:15','2026-09-25 07:24:15'),(52,1,'UPDATE','RecoveryAgent','Updated recovery agent ID: 3','{\"remarks\": \"Dummy recovery agent (password: password)\", \"user_id\": 7, \"branch_id\": 1, \"is_active\": true}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-agents/3','PUT','2026-09-25 07:24:29','2026-09-25 07:24:29'),(53,1,'INDEX','RecoveryAgent','Recovery agents index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-agents?page=1','GET','2026-09-25 07:24:30','2026-09-25 07:24:30'),(54,1,'CREATE','Branch','Created branch: Nugekoda','{\"fax\": null, \"city\": \"Colombo\", \"code\": \"NUG\", \"name\": \"Nugekoda\", \"email\": null, \"zone_id\": 1, \"is_active\": true, \"region_id\": 1, \"branch_type\": \"city\", \"postal_code\": null, \"province_id\": 1, \"opening_date\": \"2026-09-17\", \"address_line1\": \"12, Main rd\", \"address_line2\": null, \"phone_primary\": \"0752932640\", \"is_head_office\": false, \"phone_secondary\": null}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/branches','POST','2026-09-25 07:48:34','2026-09-25 07:48:34'),(55,1,'UPDATE','Branch','Updated branch: Head Office','{\"fax\": null, \"city\": \"Colombo\", \"code\": \"BR-MAIN-001\", \"name\": \"Head Office\", \"email\": \"headoffice@cdpcapital.lk\", \"zone_id\": 1, \"latitude\": 6.9271, \"is_active\": true, \"longitude\": 79.8612, \"region_id\": 1, \"branch_type\": \"main\", \"postal_code\": \"00300\", \"province_id\": 1, \"opening_date\": \"2026-09-24\", \"address_line1\": \"No. 10, Galle Road\", \"address_line2\": null, \"phone_primary\": \"+94 11 000 0000\", \"is_head_office\": true, \"phone_secondary\": null}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/branches/1','PUT','2026-09-25 07:48:52','2026-09-25 07:48:52'),(56,1,'EMAIL_SENT','User','User login credentials email sent successfully to: sulani98@gmail.com','{\"user_id\": 8}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/users','POST','2026-09-25 07:54:49','2026-09-25 07:54:49'),(57,1,'CREATE','User','Created user: EMP-NUG-001','{\"name\": \"Pathma\", \"role\": \"Employee\", \"email\": \"sulani98@gmail.com\", \"password\": \"[REDACTED]\", \"username\": \"EMP-NUG-001\", \"zonal_id\": 1, \"branch_id\": 2, \"can_login\": true, \"id_number\": \"1998214354678\", \"is_active\": true, \"region_id\": 1, \"user_type\": \"staff\", \"employee_id\": 1, \"province_id\": 1, \"employee_code\": \"EMP-NUG-001\", \"password_changed_at\": \"2026-09-25T07:54:47.434228Z\"}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/users','POST','2026-09-25 07:54:49','2026-09-25 07:54:49'),(58,1,'CREATE','Role','Created role: Staff','{\"name\": \"Staff\", \"permissions\": [74, 76, 73, 75]}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/roles','POST','2026-09-25 07:56:15','2026-09-25 07:56:15'),(59,1,'UPDATE','User','Updated user: EMP-NUG-001','{\"name\": \"Pathma\", \"role\": \"Staff\", \"email\": \"sulani98@gmail.com\", \"username\": \"EMP-NUG-001\", \"zonal_id\": 1, \"branch_id\": 2, \"can_login\": true, \"id_number\": \"1998214354678\", \"is_active\": true, \"region_id\": 1, \"user_type\": \"staff\", \"province_id\": 1, \"employee_code\": \"EMP-NUG-001\"}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/users/8','PUT','2026-09-25 07:56:42','2026-09-25 07:56:42'),(60,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 08:05:21','2026-09-25 08:05:21'),(61,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-25&start_date=2026-09-01','GET','2026-09-25 08:05:22','2026-09-25 08:05:22'),(62,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 08:05:22','2026-09-25 08:05:22'),(63,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 09:07:11','2026-09-25 09:07:11'),(64,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-25&start_date=2026-09-01','GET','2026-09-25 09:07:13','2026-09-25 09:07:13'),(65,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 09:07:14','2026-09-25 09:07:14'),(66,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 09:08:48','2026-09-25 09:08:48'),(67,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-25&start_date=2026-09-01','GET','2026-09-25 09:08:49','2026-09-25 09:08:49'),(68,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 09:08:50','2026-09-25 09:08:50'),(69,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 09:09:30','2026-09-25 09:09:30'),(70,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 09:09:31','2026-09-25 09:09:31'),(71,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:10:01','2026-09-25 09:10:01'),(72,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:10:02','2026-09-25 09:10:02'),(73,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:20:18','2026-09-25 09:20:18'),(74,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:20:19','2026-09-25 09:20:19'),(75,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:20:59','2026-09-25 09:20:59'),(76,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:21:00','2026-09-25 09:21:00'),(77,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:21:03','2026-09-25 09:21:03'),(78,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:21:03','2026-09-25 09:21:03'),(79,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 09:21:23','2026-09-25 09:21:23'),(80,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 09:21:24','2026-09-25 09:21:24'),(81,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:21:29','2026-09-25 09:21:29'),(82,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:21:29','2026-09-25 09:21:29'),(83,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 3, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-09-25 09:21:41','2026-09-25 09:21:41'),(84,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": {\"search\": \"P\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=P','GET','2026-09-25 09:39:32','2026-09-25 09:39:32'),(85,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"200012345678\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=200012345678','GET','2026-09-25 09:42:22','2026-09-25 09:42:22'),(86,1,'INDEX','Guarantor','Guarantors index accessed','{\"count\": 0, \"filters\": {\"search\": \"200087654321\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors?per_page=50&search=200087654321','GET','2026-09-25 09:42:23','2026-09-25 09:42:23'),(87,1,'CREATE','LoanApplication','Created loan application ID: 1','{\"status\": \"submitted\", \"branch_id\": 1, \"applied_at\": \"2026-09-25T09:42:24.338107Z\", \"applied_by\": 1, \"customer_id\": 2, \"term_months\": 12, \"interest_rate\": \"10.000\", \"interest_type\": \"flat\", \"application_id\": 1, \"processing_fee\": 1500, \"loan_product_id\": 1, \"recommender_nic\": null, \"recommender_name\": null, \"requested_amount\": 500000, \"recommender_phone\": null, \"collateral_policy_number\": null, \"recommender_employee_code\": null, \"recommended_by_employee_id\": null}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications','POST','2026-09-25 09:42:24','2026-09-25 09:42:24'),(88,1,'CREATE','Guarantor','Created guarantor: Rifkey','{\"type\": \"guarantor_1\", \"salary\": 125000, \"id_type\": \"NIC\", \"full_name\": \"Rifkey\", \"id_number\": \"200012345678\", \"occupation\": \"SE\", \"customer_id\": \"2\", \"employer_name\": \"CDP\", \"phone_primary\": \"0752932640\", \"employment_status\": \"Employed\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors','POST','2026-09-25 09:42:25','2026-09-25 09:42:25'),(89,1,'CREATE','LoanApplicationGuarantor','Added guarantor ID 1 to loan application ID 1','{\"status\": \"pending\", \"guarantor_id\": 1, \"guarantor_type\": \"guarantor_1\", \"loan_application_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors','POST','2026-09-25 09:42:26','2026-09-25 09:42:26'),(90,1,'CREATE','Document','Uploaded document: WhatsApp Image 2026-09-18 at 4.11.59 PM','{\"file_path\": \"uploads/documents/WhatsApp_Image_2026-09-18_at_4_11_59_PM_bVxCeUQC.jpeg\", \"is_active\": \"1\", \"customer_id\": \"2\", \"uploaded_at\": \"2026-09-25T09:42:26.796294Z\", \"uploaded_by\": 1, \"guarantor_id\": \"1\", \"document_name\": \"WhatsApp Image 2026-09-18 at 4.11.59 PM\", \"document_type\": \"nic_copy\", \"loan_application_id\": \"1\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-09-25 09:42:26','2026-09-25 09:42:26'),(91,1,'CREATE','Guarantor','Created guarantor: Inshath','{\"type\": \"guarantor_2\", \"salary\": 125000, \"id_type\": \"NIC\", \"full_name\": \"Inshath\", \"id_number\": \"200087654321\", \"occupation\": \"SE\", \"customer_id\": \"2\", \"employer_name\": \"CDP\", \"phone_primary\": \"0752932640\", \"employment_status\": \"Employed\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/guarantors','POST','2026-09-25 09:42:27','2026-09-25 09:42:27'),(92,1,'CREATE','LoanApplicationGuarantor','Added guarantor ID 2 to loan application ID 1','{\"status\": \"pending\", \"guarantor_id\": 2, \"guarantor_type\": \"guarantor_2\", \"loan_application_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors','POST','2026-09-25 09:42:28','2026-09-25 09:42:28'),(93,1,'CREATE','Document','Uploaded document: WhatsApp Image 2026-09-18 at 4.11.59 PM','{\"file_path\": \"uploads/documents/WhatsApp_Image_2026-09-18_at_4_11_59_PM_TcK4mCJo.jpeg\", \"is_active\": \"1\", \"customer_id\": \"2\", \"uploaded_at\": \"2026-09-25T09:42:29.122587Z\", \"uploaded_by\": 1, \"guarantor_id\": \"2\", \"document_name\": \"WhatsApp Image 2026-09-18 at 4.11.59 PM\", \"document_type\": \"nic_copy\", \"loan_application_id\": \"1\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-09-25 09:42:29','2026-09-25 09:42:29'),(94,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:42:30','2026-09-25 09:42:30'),(95,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:42:30','2026-09-25 09:42:30'),(96,1,'INDEX','GlobalSearch','Global search performed','{\"found\": true, \"id_type\": \"NIC\", \"user_id\": 1, \"id_number\": \"200012345678\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/global-search?id_number=200012345678&id_type=NIC','GET','2026-09-25 09:42:58','2026-09-25 09:42:58'),(97,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": \"nic\", \"success\": false, \"user_id\": 1, \"id_number\": \"200012345678\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-09-25 09:43:00','2026-09-25 09:43:00'),(98,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": \"nic\", \"success\": false, \"user_id\": 1, \"id_number\": \"200012345678\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-09-25 09:43:01','2026-09-25 09:43:01'),(99,1,'INDEX','GlobalSearch','Global search performed','{\"found\": true, \"id_type\": \"NIC\", \"user_id\": 1, \"id_number\": \"200012345678\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/global-search?id_number=200012345678&id_type=NIC','GET','2026-09-25 09:43:01','2026-09-25 09:43:01'),(100,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?loan_application_id=1&per_page=100','GET','2026-09-25 09:43:02','2026-09-25 09:43:02'),(101,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?loan_application_id=1&per_page=100','GET','2026-09-25 09:43:03','2026-09-25 09:43:03'),(102,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:43:31','2026-09-25 09:43:31'),(103,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:43:31','2026-09-25 09:43:31'),(104,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-09-25 09:43:35','2026-09-25 09:43:35'),(105,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-09-25 09:43:37','2026-09-25 09:43:37'),(106,1,'SHOW','Document','Document file opened: uploads/documents/2_WhatsApp_Image_2026-09-18_at_4_11_59_PM_RlG8Oubs.jpeg','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents/file?path=uploads%2Fdocuments%2F2_WhatsApp_Image_2026-09-18_at_4_11_59_PM_RlG8Oubs.jpeg','GET','2026-09-25 09:43:42','2026-09-25 09:43:42'),(107,1,'UPDATE','Branch','Updated branch: Head Office','{\"fax\": null, \"city\": \"Colombo\", \"code\": \"HCOL\", \"name\": \"Head Office\", \"email\": \"headoffice@cdpcapital.lk\", \"zone_id\": 1, \"latitude\": 6.9271, \"is_active\": true, \"longitude\": 79.8612, \"region_id\": 1, \"branch_type\": \"main\", \"postal_code\": \"00300\", \"province_id\": 1, \"opening_date\": \"2026-09-23\", \"address_line1\": \"No. 10, Galle Road\", \"address_line2\": null, \"phone_primary\": \"+94 11 000 0000\", \"is_head_office\": true, \"phone_secondary\": null}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/branches/1','PUT','2026-09-25 09:46:50','2026-09-25 09:46:50'),(108,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:47:06','2026-09-25 09:47:06'),(109,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:47:06','2026-09-25 09:47:06'),(110,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-09-25 09:47:34','2026-09-25 09:47:34'),(111,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-09-25 09:47:35','2026-09-25 09:47:35'),(112,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 09:48:23','2026-09-25 09:48:23'),(113,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-25&start_date=2026-09-01','GET','2026-09-25 09:48:23','2026-09-25 09:48:23'),(114,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 09:48:24','2026-09-25 09:48:24'),(115,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:52:40','2026-09-25 09:52:40'),(116,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 09:52:41','2026-09-25 09:52:41'),(117,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-09-25 09:52:48','2026-09-25 09:52:48'),(118,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-09-25 09:52:49','2026-09-25 09:52:49'),(119,1,'UPDATE','LoanApplication','Loan application ID: 1 failed review','{\"loan_application_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications/1/review-fail','PATCH','2026-09-25 09:53:14','2026-09-25 09:53:14'),(120,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": {\"search\": \"B\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?per_page=100&search=B&status=submitted%2Creview_failed%2Creviewed%2Creverify%2Creopened','GET','2026-09-25 09:54:52','2026-09-25 09:54:52'),(121,1,'INDEX','Document','Documents index accessed','{\"count\": 3, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-09-25 09:54:56','2026-09-25 09:54:56'),(122,1,'INDEX','LoanApplicationGuarantor','Loan application guarantors index accessed','{\"count\": 2, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors?loan_application_id=1&page=1','GET','2026-09-25 09:54:57','2026-09-25 09:54:57'),(123,1,'CREATE','Document','Uploaded document: Billing Proof','{\"remarks\": \"Application: APP-BRMAIN001-2609250001 | Customer: Piranya Paskaran\", \"file_path\": \"uploads/documents/Billing_Proof_OEkgU3V5.pdf\", \"customer_id\": \"2\", \"uploaded_at\": \"2026-09-25T09:57:04.687649Z\", \"uploaded_by\": 1, \"document_name\": \"Billing Proof\", \"document_type\": \"billing_proof\", \"loan_application_id\": \"1\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-09-25 09:57:04','2026-09-25 09:57:04'),(124,1,'CREATE','Document','Uploaded document: Salary Slips (last 3 months)','{\"remarks\": \"Application: APP-BRMAIN001-2609250001 | Customer: Piranya Paskaran\", \"file_path\": \"uploads/documents/Salary_Slips_last_3_months_CgsamkNc.pdf\", \"customer_id\": \"2\", \"uploaded_at\": \"2026-09-25T09:57:27.853638Z\", \"uploaded_by\": 1, \"document_name\": \"Salary Slips (last 3 months)\", \"document_type\": \"salary_slip\", \"loan_application_id\": \"1\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-09-25 09:57:27','2026-09-25 09:57:27'),(125,1,'CREATE','Document','Uploaded document: Salary Confirmation Letter','{\"remarks\": \"Application: APP-BRMAIN001-2609250001 | Customer: Piranya Paskaran\", \"file_path\": \"uploads/documents/Salary_Confirmation_Letter_GwhRbxab.pdf\", \"customer_id\": \"2\", \"uploaded_at\": \"2026-09-25T09:57:35.077446Z\", \"uploaded_by\": 1, \"document_name\": \"Salary Confirmation Letter\", \"document_type\": \"salary_assignment_letter\", \"loan_application_id\": \"1\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-09-25 09:57:35','2026-09-25 09:57:35'),(126,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 10:20:33','2026-09-25 10:20:33'),(127,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 10:20:33','2026-09-25 10:20:33'),(128,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 10:26:39','2026-09-25 10:26:39'),(129,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-25 10:26:41','2026-09-25 10:26:41'),(130,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 10:34:00','2026-09-25 10:34:00'),(131,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-25 10:34:01','2026-09-25 10:34:01'),(132,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 10:48:16','2026-09-25 10:48:16'),(133,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-25 10:48:24','2026-09-25 10:48:24'),(134,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-26 06:54:53','2026-09-26 06:54:53'),(135,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-26 06:55:01','2026-09-26 06:55:01'),(136,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-27 05:42:13','2026-09-27 05:42:13'),(137,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-27&start_date=2026-09-01','GET','2026-09-27 05:42:15','2026-09-27 05:42:15'),(138,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-27 05:42:16','2026-09-27 05:42:16'),(139,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-27&start_date=2026-09-01','GET','2026-09-27 05:42:29','2026-09-27 05:42:29'),(140,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 04:32:14','2026-09-28 04:32:14'),(141,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-28&start_date=2026-09-01','GET','2026-09-28 04:32:16','2026-09-28 04:32:16'),(142,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 04:32:17','2026-09-28 04:32:17'),(143,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 04:34:04','2026-09-28 04:34:04'),(144,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-28&start_date=2026-09-01','GET','2026-09-28 04:34:06','2026-09-28 04:34:06'),(145,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 04:34:07','2026-09-28 04:34:07'),(146,1,'INDEX','GlobalSearch','Global search performed','{\"found\": false, \"id_type\": \"NIC\", \"user_id\": 1, \"id_number\": \"767664745V\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/global-search?id_number=767664745V&id_type=NIC','GET','2026-09-28 04:36:23','2026-09-28 04:36:23'),(147,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": \"nic\", \"success\": true, \"user_id\": 1, \"id_number\": \"767664745V\", \"status_code\": 200}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-09-28 04:36:26','2026-09-28 04:36:26'),(148,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": \"nic\", \"success\": true, \"user_id\": 1, \"id_number\": \"767664745V\", \"status_code\": 200}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-09-28 04:36:29','2026-09-28 04:36:29'),(149,1,'INDEX','GlobalSearch','Global search performed','{\"found\": false, \"id_type\": \"NIC\", \"user_id\": 1, \"id_number\": \"767664745V\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/global-search?id_number=767664745V&id_type=NIC','GET','2026-09-28 04:36:30','2026-09-28 04:36:30'),(150,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-28 04:36:50','2026-09-28 04:36:50'),(151,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-28 04:36:51','2026-09-28 04:36:51'),(152,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?page=1','GET','2026-09-28 04:36:58','2026-09-28 04:36:58'),(153,1,'INDEX','GroupLoan','Group loans index accessed','{\"count\": 0, \"filters\": {\"status\": \"available\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/group-loans/status/available?page=1&per_page=15','GET','2026-09-28 04:37:07','2026-09-28 04:37:07'),(154,1,'INDEX','GroupLoan','Group loans index accessed','{\"count\": 0, \"filters\": {\"status\": \"available\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/group-loans/status/available?page=1&per_page=15','GET','2026-09-28 04:37:09','2026-09-28 04:37:09'),(155,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-28 04:37:18','2026-09-28 04:37:18'),(156,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-09-28 04:37:19','2026-09-28 04:37:19'),(157,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-09-28 04:37:51','2026-09-28 04:37:51'),(158,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-09-28 04:37:52','2026-09-28 04:37:52'),(159,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-09-28 04:37:55','2026-09-28 04:37:55'),(160,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-09-28 04:37:56','2026-09-28 04:37:56'),(161,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-28 04:38:38','2026-09-28 04:38:38'),(162,1,'INDEX','Customer','Customers index accessed','{\"count\": 2, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-28 04:38:39','2026-09-28 04:38:39'),(163,1,'EMAIL_SENT','Customer','Registration credentials email sent to customer: vedhasiricdp@gmail.com','{\"email\": \"vedhasiricdp@gmail.com\", \"customer_id\": 3, \"notification_id\": 4}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-09-28 04:53:54','2026-09-28 04:53:54'),(164,1,'CREATE','Customer','Customer created with associated details','{\"creator_id\": 1, \"customer_code\": \"CUS0003\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-09-28 04:53:54','2026-09-28 04:53:54'),(165,1,'CREATE','User','Login account created for customer: CUS0003','{\"user_id\": 9, \"username\": \"thipanujan\", \"customer_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers','POST','2026-09-28 04:53:54','2026-09-28 04:53:54'),(166,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-28 04:53:57','2026-09-28 04:53:57'),(167,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-09-28 04:53:58','2026-09-28 04:53:58'),(168,9,'INDEX','CustomerPortal','Customer viewed dashboard overview','{\"user_id\": 9, \"customer_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/my/dashboard','GET','2026-09-28 04:55:25','2026-09-28 04:55:25'),(169,9,'INDEX','CustomerPortal','Customer viewed dashboard overview','{\"user_id\": 9, \"customer_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/my/dashboard','GET','2026-09-28 04:55:26','2026-09-28 04:55:26'),(170,9,'INDEX','CustomerPortal','Customer viewed dashboard overview','{\"user_id\": 9, \"customer_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/my/dashboard','GET','2026-09-28 04:56:12','2026-09-28 04:56:12'),(171,9,'INDEX','CustomerPortal','Customer viewed dashboard overview','{\"user_id\": 9, \"customer_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/my/dashboard','GET','2026-09-28 04:56:13','2026-09-28 04:56:13'),(172,9,'INDEX','CustomerPortal','Customer viewed dashboard overview','{\"user_id\": 9, \"customer_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/my/dashboard','GET','2026-09-28 05:26:40','2026-09-28 05:26:40'),(173,9,'INDEX','CustomerPortal','Customer viewed dashboard overview','{\"user_id\": 9, \"customer_id\": 3}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/my/dashboard','GET','2026-09-28 05:26:40','2026-09-28 05:26:40'),(174,NULL,'UPDATE','User','Accounts locked for an unchanged temporary password: 1.','{\"locked\": 1, \"user_ids\": [9]}','info','127.0.0.1','Symfony','http://127.0.0.1:8000','GET','2026-09-28 05:42:00','2026-09-28 05:42:00'),(175,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 06:07:33','2026-09-28 06:07:33'),(176,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-28&start_date=2026-09-01','GET','2026-09-28 06:07:34','2026-09-28 06:07:34'),(177,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 06:07:34','2026-09-28 06:07:34'),(178,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 08:27:55','2026-09-28 08:27:55'),(179,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-28&start_date=2026-09-01','GET','2026-09-28 08:27:56','2026-09-28 08:27:56'),(180,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 08:27:57','2026-09-28 08:27:57'),(181,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 09:43:07','2026-09-28 09:43:07'),(182,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-28&start_date=2026-09-01','GET','2026-09-28 09:43:08','2026-09-28 09:43:08'),(183,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 09:43:08','2026-09-28 09:43:08'),(184,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 10:36:53','2026-09-28 10:36:53'),(185,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-28&start_date=2026-09-01','GET','2026-09-28 10:36:55','2026-09-28 10:36:55'),(186,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 10:36:56','2026-09-28 10:36:56'),(187,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 11:05:08','2026-09-28 11:05:08'),(188,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-09-28&start_date=2026-09-01','GET','2026-09-28 11:05:10','2026-09-28 11:05:10'),(189,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-09-28 11:05:11','2026-09-28 11:05:11'),(270,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-02 04:00:43','2026-10-02 04:00:43'),(271,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-02&start_date=2026-10-01','GET','2026-10-02 04:00:44','2026-10-02 04:00:44'),(272,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-02 04:00:45','2026-10-02 04:00:45'),(273,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-02 04:00:46','2026-10-02 04:00:46'),(274,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-02 04:00:47','2026-10-02 04:00:47'),(275,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 3, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-02 04:00:55','2026-10-02 04:00:55'),(276,1,'INDEX','LoanSecurity','CDP investment looked up for a loan security','{\"user_id\": 1, \"customer_id\": null, \"policy_number\": \"CDP-MNR-00000238\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-securities/investment-lookup','POST','2026-10-02 04:03:09','2026-10-02 04:03:09'),(277,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-02 04:18:20','2026-10-02 04:18:20'),(278,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-02 04:18:22','2026-10-02 04:18:22'),(279,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 3, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-02 04:18:29','2026-10-02 04:18:29'),(280,1,'INDEX','LoanSecurity','CDP investment looked up for a loan security','{\"user_id\": 1, \"customer_id\": null, \"policy_number\": \"CDP-MNR-00000238\"}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-securities/investment-lookup','POST','2026-10-02 04:21:19','2026-10-02 04:21:19'),(281,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-02 04:29:56','2026-10-02 04:29:56'),(282,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-02 04:29:59','2026-10-02 04:29:59'),(283,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-02 04:34:44','2026-10-02 04:34:44'),(284,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-02 04:34:47','2026-10-02 04:34:47'),(285,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 3, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-02 04:37:41','2026-10-02 04:37:41'),(286,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-02 04:40:22','2026-10-02 04:40:22'),(287,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-02 04:40:22','2026-10-02 04:40:22'),(288,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 3, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-02 04:40:31','2026-10-02 04:40:31'),(456,1,'INDEX','LoanSecurity','CDP investment looked up for a loan security','{\"user_id\": 1, \"customer_ids\": [], \"policy_number\": \"CDP-MNR-00000238\"}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-securities/investment-lookup','POST','2026-10-02 06:00:23','2026-10-02 06:00:23'),(457,1,'INDEX','Customer','Customers index accessed','{\"count\": 0, \"filters\": {\"search\": \"747473803V\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=747473803V','GET','2026-10-02 06:02:49','2026-10-02 06:02:49'),(458,1,'INDEX','Customer','Customers index accessed','{\"count\": 0, \"filters\": {\"search\": \"747473803\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1&search=747473803','GET','2026-10-02 06:02:53','2026-10-02 06:02:53'),(538,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-02 06:12:15','2026-10-02 06:12:15'),(539,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-02 06:12:18','2026-10-02 06:12:18'),(540,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 2, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-02 06:12:21','2026-10-02 06:12:21'),(541,1,'INDEX','LoanSecurity','CDP investment looked up for a loan security','{\"user_id\": 1, \"customer_ids\": [], \"policy_number\": \"CDP-MNR-00000238\"}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-securities/investment-lookup','POST','2026-10-02 06:12:59','2026-10-02 06:12:59'),(640,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-02 07:45:48','2026-10-02 07:45:48'),(641,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-02&start_date=2026-10-01','GET','2026-10-02 07:45:49','2026-10-02 07:45:49'),(642,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-02 07:45:49','2026-10-02 07:45:49'),(643,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-02 07:46:01','2026-10-02 07:46:01'),(644,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-02 07:46:01','2026-10-02 07:46:01'),(645,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-02 07:46:04','2026-10-02 07:46:04'),(646,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-02 07:46:05','2026-10-02 07:46:05'),(647,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 2, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-02 07:46:15','2026-10-02 07:46:15'),(648,1,'UPDATE','Setting','Updated setting: cdp_investment_max_loan_percentage','{\"key\": \"cdp_investment_max_loan_percentage\", \"value\": \"50\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/settings/cdp_investment_max_loan_percentage','PUT','2026-10-02 07:46:40','2026-10-02 07:46:40'),(649,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-02 07:47:02','2026-10-02 07:47:02'),(650,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-02 07:47:03','2026-10-02 07:47:03'),(651,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 2, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-02 07:47:12','2026-10-02 07:47:12'),(745,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?page=1&per_page=20','GET','2026-10-02 08:10:16','2026-10-02 08:10:16'),(746,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?page=1&per_page=20','GET','2026-10-02 08:10:17','2026-10-02 08:10:17'),(747,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?loan_application_id=1','GET','2026-10-02 08:10:24','2026-10-02 08:10:24'),(748,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?loan_application_id=1','GET','2026-10-02 08:10:25','2026-10-02 08:10:25'),(749,1,'CREATE','LegalDocument','Created legal document LEG-000001','{\"status\": \"created\", \"legal_document_id\": 1, \"loan_application_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents','POST','2026-10-02 08:11:26','2026-10-02 08:11:26'),(953,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-05 06:28:37','2026-10-05 06:28:37'),(954,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-05&start_date=2026-10-01','GET','2026-10-05 06:28:37','2026-10-05 06:28:37'),(955,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-05 06:28:38','2026-10-05 06:28:38'),(956,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-05 06:28:39','2026-10-05 06:28:39'),(957,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-05 06:28:40','2026-10-05 06:28:40'),(958,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-05 06:29:40','2026-10-05 06:29:40'),(959,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-05 06:29:40','2026-10-05 06:29:40'),(960,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 2, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-05 06:29:51','2026-10-05 06:29:51'),(961,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-06 04:51:49','2026-10-06 04:51:49'),(962,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-06&start_date=2026-10-01','GET','2026-10-06 04:51:50','2026-10-06 04:51:50'),(963,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-06 04:51:50','2026-10-06 04:51:50'),(964,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 04:51:57','2026-10-06 04:51:57'),(965,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 04:51:58','2026-10-06 04:51:58'),(966,1,'INDEX','Document','Documents index accessed','{\"count\": 6, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-06 04:52:05','2026-10-06 04:52:05'),(967,1,'INDEX','Document','Documents index accessed','{\"count\": 6, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-06 04:52:08','2026-10-06 04:52:08'),(968,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 04:52:18','2026-10-06 04:52:18'),(969,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 04:52:19','2026-10-06 04:52:19'),(970,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 05:02:52','2026-10-06 05:02:52'),(971,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 05:02:55','2026-10-06 05:02:55'),(972,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 05:03:05','2026-10-06 05:03:05'),(973,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 05:03:12','2026-10-06 05:03:12'),(974,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 05:28:31','2026-10-06 05:28:31'),(975,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 05:28:32','2026-10-06 05:28:32'),(976,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 05:30:06','2026-10-06 05:30:06'),(977,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 05:30:06','2026-10-06 05:30:06'),(978,1,'INDEX','Document','Documents index accessed','{\"count\": 6, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-06 05:30:10','2026-10-06 05:30:10'),(979,1,'INDEX','Document','Documents index accessed','{\"count\": 6, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-06 05:30:11','2026-10-06 05:30:11'),(980,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 05:53:49','2026-10-06 05:53:49'),(981,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 05:53:49','2026-10-06 05:53:49'),(982,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 05:58:31','2026-10-06 05:58:31'),(983,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 05:58:32','2026-10-06 05:58:32'),(984,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 05:58:36','2026-10-06 05:58:36'),(985,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 05:58:37','2026-10-06 05:58:37'),(986,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 2, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-06 05:59:45','2026-10-06 05:59:45'),(987,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-06 08:49:13','2026-10-06 08:49:13'),(988,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-06&start_date=2026-10-01','GET','2026-10-06 08:49:14','2026-10-06 08:49:14'),(989,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-06 08:49:15','2026-10-06 08:49:15'),(990,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 08:49:19','2026-10-06 08:49:19'),(991,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 08:49:20','2026-10-06 08:49:20'),(992,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 2, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-06 08:50:00','2026-10-06 08:50:00'),(993,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 08:50:56','2026-10-06 08:50:56'),(994,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 08:50:59','2026-10-06 08:50:59'),(995,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 08:51:01','2026-10-06 08:51:01'),(996,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 08:51:02','2026-10-06 08:51:02'),(997,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 08:51:03','2026-10-06 08:51:03'),(998,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 08:51:05','2026-10-06 08:51:05'),(999,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-06 09:12:09','2026-10-06 09:12:09'),(1000,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-06 09:12:12','2026-10-06 09:12:12'),(1001,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 09:23:51','2026-10-06 09:23:51'),(1002,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 09:23:54','2026-10-06 09:23:54'),(1003,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:23:55','2026-10-06 09:23:55'),(1004,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:23:56','2026-10-06 09:23:56'),(1005,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 09:24:15','2026-10-06 09:24:15'),(1006,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 09:24:18','2026-10-06 09:24:18'),(1007,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:24:20','2026-10-06 09:24:20'),(1008,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 09:24:21','2026-10-06 09:24:21'),(1009,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:24:23','2026-10-06 09:24:23'),(1010,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 09:24:24','2026-10-06 09:24:24'),(1011,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:26:23','2026-10-06 09:26:23'),(1012,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:26:24','2026-10-06 09:26:24'),(1013,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:26:41','2026-10-06 09:26:41'),(1014,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:26:42','2026-10-06 09:26:42'),(1015,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:26:50','2026-10-06 09:26:50'),(1016,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:26:52','2026-10-06 09:26:52'),(1017,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 09:26:53','2026-10-06 09:26:53'),(1018,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:26:55','2026-10-06 09:26:55'),(1019,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 09:26:56','2026-10-06 09:26:56'),(1020,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:26:57','2026-10-06 09:26:57'),(1021,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:27:03','2026-10-06 09:27:03'),(1022,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:27:06','2026-10-06 09:27:06'),(1023,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:27:08','2026-10-06 09:27:08'),(1024,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:27:13','2026-10-06 09:27:13'),(1025,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-06&start_date=2026-10-01','GET','2026-10-06 09:27:15','2026-10-06 09:27:15'),(1026,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:27:20','2026-10-06 09:27:20'),(1027,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 09:27:25','2026-10-06 09:27:25'),(1028,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:27:28','2026-10-06 09:27:28'),(1029,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?page=1&per_page=20','GET','2026-10-06 09:27:29','2026-10-06 09:27:29'),(1030,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?month=August%202026','GET','2026-10-06 09:27:36','2026-10-06 09:27:36'),(1031,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 09:27:37','2026-10-06 09:27:37'),(1032,1,'INDEX','Payment','Payments index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/payments?page=1','GET','2026-10-06 09:27:39','2026-10-06 09:27:39'),(1033,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:27:42','2026-10-06 09:27:42'),(1034,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?page=1&per_page=20','GET','2026-10-06 09:27:43','2026-10-06 09:27:43'),(1035,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-10-06 09:27:45','2026-10-06 09:27:45'),(1036,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-10-06 09:27:46','2026-10-06 09:27:46'),(1037,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?month=August%202026','GET','2026-10-06 09:27:49','2026-10-06 09:27:49'),(1038,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:27:50','2026-10-06 09:27:50'),(1039,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-06&start_date=2026-10-01','GET','2026-10-06 09:27:51','2026-10-06 09:27:51'),(1040,1,'INDEX','Payment','Payments index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/payments?page=1','GET','2026-10-06 09:27:53','2026-10-06 09:27:53'),(1041,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-10-06 09:27:56','2026-10-06 09:27:56'),(1042,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-10-06 09:27:57','2026-10-06 09:27:57'),(1043,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:28:05','2026-10-06 09:28:05'),(1044,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:28:06','2026-10-06 09:28:06'),(1045,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:28:08','2026-10-06 09:28:08'),(1046,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:28:09','2026-10-06 09:28:09'),(1047,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-06 09:43:16','2026-10-06 09:43:16'),(1048,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:43:16','2026-10-06 09:43:16'),(1049,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-06 09:43:17','2026-10-06 09:43:17'),(1050,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:43:18','2026-10-06 09:43:18'),(1051,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-06 09:48:43','2026-10-06 09:48:43'),(1052,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:48:44','2026-10-06 09:48:44'),(1053,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-06 09:48:45','2026-10-06 09:48:45'),(1054,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:48:46','2026-10-06 09:48:46'),(1055,1,'INDEX','Document','Documents index accessed','{\"count\": 6, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&page=1','GET','2026-10-06 09:53:01','2026-10-06 09:53:01'),(1056,1,'INDEX','Document','Documents index accessed','{\"count\": 6, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&page=1','GET','2026-10-06 09:53:03','2026-10-06 09:53:03'),(1057,1,'INDEX','Document','Documents index accessed','{\"count\": 6, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&page=1','GET','2026-10-06 09:53:42','2026-10-06 09:53:42'),(1058,1,'INDEX','Document','Documents index accessed','{\"count\": 6, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&page=1','GET','2026-10-06 09:53:44','2026-10-06 09:53:44'),(1059,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 09:57:02','2026-10-06 09:57:02'),(1060,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-06 09:57:05','2026-10-06 09:57:05'),(1061,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:57:29','2026-10-06 09:57:29'),(1062,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:57:31','2026-10-06 09:57:31'),(1063,1,'INDEX','GlobalSearch','Global search performed','{\"found\": true, \"id_type\": \"NIC\", \"user_id\": 1, \"id_number\": \"200012345678\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/global-search?id_number=200012345678&id_type=NIC','GET','2026-10-06 09:57:43','2026-10-06 09:57:43'),(1064,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": \"nic\", \"success\": false, \"user_id\": 1, \"id_number\": \"200012345678\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-06 09:57:46','2026-10-06 09:57:46'),(1065,1,'INDEX','CdpCustomerVerification','CDP Connect customer verification performed','{\"id_type\": \"nic\", \"success\": false, \"user_id\": 1, \"id_number\": \"200012345678\", \"status_code\": 404}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/cdp/verify-customer','POST','2026-10-06 09:57:49','2026-10-06 09:57:49'),(1066,1,'INDEX','GlobalSearch','Global search performed','{\"found\": true, \"id_type\": \"NIC\", \"user_id\": 1, \"id_number\": \"200012345678\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/global-search?id_number=200012345678&id_type=NIC','GET','2026-10-06 09:57:50','2026-10-06 09:57:50'),(1067,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?loan_application_id=1&per_page=100','GET','2026-10-06 09:57:52','2026-10-06 09:57:52'),(1068,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?loan_application_id=1&per_page=100','GET','2026-10-06 09:57:53','2026-10-06 09:57:53'),(1069,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:58:02','2026-10-06 09:58:02'),(1070,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-06 09:58:03','2026-10-06 09:58:03'),(1071,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-06 10:23:06','2026-10-06 10:23:06'),(1072,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-06 10:23:08','2026-10-06 10:23:08'),(1073,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-07 06:58:39','2026-10-07 06:58:39'),(1074,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-07&start_date=2026-10-01','GET','2026-10-07 06:58:40','2026-10-07 06:58:40'),(1075,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-07 06:58:41','2026-10-07 06:58:41'),(1076,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-07 06:58:42','2026-10-07 06:58:42'),(1077,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-07 06:58:42','2026-10-07 06:58:42'),(1078,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-07 06:58:48','2026-10-07 06:58:48'),(1079,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-07 06:58:49','2026-10-07 06:58:49'),(1080,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-07 06:58:50','2026-10-07 06:58:50'),(1081,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-07 06:58:51','2026-10-07 06:58:51'),(1082,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-07 06:58:58','2026-10-07 06:58:58'),(1083,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-07 06:58:58','2026-10-07 06:58:58'),(1084,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": {\"search\": \"APP-BRMAIN001-2609250001\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?per_page=100&search=%09APP-BRMAIN001-2609250001&status=submitted%2Creview_failed%2Creviewed%2Creverify%2Creopened','GET','2026-10-07 06:59:22','2026-10-07 06:59:22'),(1085,1,'INDEX','Document','Documents index accessed','{\"count\": 6, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-07 06:59:25','2026-10-07 06:59:25'),(1086,1,'INDEX','LoanApplicationGuarantor','Loan application guarantors index accessed','{\"count\": 2, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors?loan_application_id=1&page=1','GET','2026-10-07 06:59:25','2026-10-07 06:59:25'),(1087,1,'CREATE','Document','Uploaded document: NIC Copy','{\"remarks\": \"Application: APP-BRMAIN001-2609250001 | Customer: Piranya Paskaran\", \"file_path\": \"uploads/documents/NIC_Copy_R6mdaOZJ.jpeg\", \"customer_id\": \"2\", \"uploaded_at\": \"2026-10-07T06:59:49.126638Z\", \"uploaded_by\": 1, \"document_name\": \"NIC Copy\", \"document_type\": \"nic_copy\", \"loan_application_id\": \"1\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-10-07 06:59:49','2026-10-07 06:59:49'),(1088,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&page=1','GET','2026-10-07 07:00:05','2026-10-07 07:00:05'),(1089,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&page=1','GET','2026-10-07 07:00:06','2026-10-07 07:00:06'),(1090,1,'SHOW','Document','Document file opened: uploads/documents/NIC_Copy_R6mdaOZJ.jpeg','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents/file?path=uploads%2Fdocuments%2FNIC_Copy_R6mdaOZJ.jpeg','GET','2026-10-07 07:00:09','2026-10-07 07:00:09'),(1091,1,'CREATE','Signature','Created signature for customer ID: 1',NULL,'info','127.0.0.1','PostmanRuntime/2.10.1','http://127.0.0.1:8000/api/v1/signatures','POST','2026-10-07 10:43:05','2026-10-07 10:43:05'),(1092,1,'CREATE','Signature','Created signature for customer ID: 2',NULL,'info','127.0.0.1','PostmanRuntime/2.10.1','http://127.0.0.1:8000/api/v1/signatures','POST','2026-10-07 10:43:50','2026-10-07 10:43:50'),(1093,1,'DELETE','Signature','Signature deleted','{\"deleter_id\": 1, \"customer_id\": 1}','info','127.0.0.1','PostmanRuntime/2.10.1','http://127.0.0.1:8000/api/v1/signatures/1','DELETE','2026-10-07 10:57:17','2026-10-07 10:57:17'),(1094,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-07 11:02:22','2026-10-07 11:02:22'),(1095,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-07&start_date=2026-10-01','GET','2026-10-07 11:02:24','2026-10-07 11:02:24'),(1096,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-07 11:02:25','2026-10-07 11:02:25'),(1097,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-07 11:02:55','2026-10-07 11:02:55'),(1098,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-07 11:02:57','2026-10-07 11:02:57'),(1099,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-07 11:22:52','2026-10-07 11:22:52'),(1100,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-07 11:23:02','2026-10-07 11:23:02'),(1101,1,'INDEX','LoanProduct','Loan products index accessed','{\"count\": 2, \"filters\": {\"is_active\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-products?is_active=1&per_page=1000','GET','2026-10-07 11:23:16','2026-10-07 11:23:16'),(1102,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-07 11:40:43','2026-10-07 11:40:43'),(1103,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-07 11:40:46','2026-10-07 11:40:46'),(1104,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 05:43:15','2026-10-08 05:43:15'),(1105,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-08&start_date=2026-10-01','GET','2026-10-08 05:43:16','2026-10-08 05:43:16'),(1106,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 05:43:17','2026-10-08 05:43:17'),(1107,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 05:43:20','2026-10-08 05:43:20'),(1108,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 05:43:30','2026-10-08 05:43:30'),(1109,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 05:43:44','2026-10-08 05:43:44'),(1110,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 05:43:45','2026-10-08 05:43:45'),(1111,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-08 05:43:57','2026-10-08 05:43:57'),(1112,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-08 05:44:00','2026-10-08 05:44:00'),(1113,1,'SHOW','Document','Document file opened: uploads/documents/WhatsApp_Image_2026-09-18_at_4_11_59_PM_bVxCeUQC.jpeg','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents/file?path=uploads%2Fdocuments%2FWhatsApp_Image_2026-09-18_at_4_11_59_PM_bVxCeUQC.jpeg','GET','2026-10-08 05:44:15','2026-10-08 05:44:15'),(1114,1,'SHOW','Document','Document file opened: uploads/documents/NIC_Copy_R6mdaOZJ.jpeg','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents/file?path=uploads%2Fdocuments%2FNIC_Copy_R6mdaOZJ.jpeg','GET','2026-10-08 05:44:48','2026-10-08 05:44:48'),(1115,1,'UPDATE','LoanApplication','Loan application ID: 1 resubmitted for review','{\"loan_application_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications/1/resubmit','PATCH','2026-10-08 05:48:41','2026-10-08 05:48:41'),(1116,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 05:49:47','2026-10-08 05:49:47'),(1117,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 05:49:48','2026-10-08 05:49:48'),(1118,1,'CREATE','CustomerVerification','Customer KYC verification initiated for Customer #1','{\"review_id\": 1, \"customer_id\": 1, \"phone_masked\": \"075*****40\", \"snapshot_version\": 1}','info','127.0.0.1','PostmanRuntime/2.10.1','http://127.0.0.1:8000/api/v1/customers/1/verification/initiate','POST','2026-10-08 08:12:09','2026-10-08 08:12:09'),(1119,1,'CREATE','CustomerVerification','Customer KYC verification initiated for Customer #1','{\"review_id\": 2, \"customer_id\": 1, \"phone_masked\": \"075*****40\", \"snapshot_version\": 2}','info','127.0.0.1','PostmanRuntime/2.10.1','http://127.0.0.1:8000/api/v1/customers/1/verification/initiate','POST','2026-10-08 08:13:36','2026-10-08 08:13:36'),(1120,1,'UPDATE','CustomerVerification','Customer #1 KYC verified with OTP','{\"review_id\": 2, \"customer_id\": 1}','info','127.0.0.1','PostmanRuntime/2.10.1','http://127.0.0.1:8000/api/v1/customer-verifications/verify-otp','POST','2026-10-08 08:17:43','2026-10-08 08:17:43'),(1121,1,'CREATE','CustomerVerification','Customer KYC verification initiated for Customer #1','{\"review_id\": 3, \"customer_id\": 1, \"phone_masked\": \"075*****40\", \"snapshot_version\": 3}','info','127.0.0.1','PostmanRuntime/2.10.1','http://127.0.0.1:8000/api/v1/customers/1/verification/initiate','POST','2026-10-08 08:20:38','2026-10-08 08:20:38'),(1122,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 08:41:25','2026-10-08 08:41:25'),(1123,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-08&start_date=2026-10-01','GET','2026-10-08 08:41:26','2026-10-08 08:41:26'),(1124,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 08:41:27','2026-10-08 08:41:27'),(1125,1,'INDEX','Report','Loan portfolio report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/reports/loan-portfolio?page=1&per_page=15','GET','2026-10-08 08:41:32','2026-10-08 08:41:32'),(1126,1,'INDEX','Report','Loan portfolio report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/reports/loan-portfolio?page=1&per_page=15','GET','2026-10-08 08:41:36','2026-10-08 08:41:36'),(1127,1,'INDEX','Report','Recovery report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/reports/recovery?page=1&per_page=15','GET','2026-10-08 08:41:37','2026-10-08 08:41:37'),(1128,1,'INDEX','Report','Recovery report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/reports/recovery?page=1&per_page=15','GET','2026-10-08 08:41:39','2026-10-08 08:41:39'),(1129,1,'INDEX','Report','Loan portfolio report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/reports/loan-portfolio?page=1&per_page=15','GET','2026-10-08 08:41:43','2026-10-08 08:41:43'),(1130,1,'INDEX','Report','Loan portfolio report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/reports/loan-portfolio?page=1&per_page=15','GET','2026-10-08 08:41:45','2026-10-08 08:41:45'),(1131,1,'INDEX','Report','Recovery report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/reports/recovery?page=1&per_page=15','GET','2026-10-08 08:42:05','2026-10-08 08:42:05'),(1132,1,'INDEX','Report','Recovery report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/reports/recovery?page=1&per_page=15','GET','2026-10-08 08:42:06','2026-10-08 08:42:06'),(1133,1,'INDEX','Report','Customer-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/reports/customer-wise?page=1&per_page=15','GET','2026-10-08 08:42:25','2026-10-08 08:42:25'),(1134,1,'INDEX','Report','Customer-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/reports/customer-wise?page=1&per_page=15','GET','2026-10-08 08:42:28','2026-10-08 08:42:28'),(1135,1,'INDEX','Report','Branch-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/reports/branch-wise?page=1&per_page=15','GET','2026-10-08 08:42:29','2026-10-08 08:42:29'),(1136,1,'INDEX','Report','Branch-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/reports/branch-wise?page=1&per_page=15','GET','2026-10-08 08:42:30','2026-10-08 08:42:30'),(1137,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 08:42:40','2026-10-08 08:42:40'),(1138,1,'INDEX','Report','Branch-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/reports/branch-wise?page=1&per_page=15','GET','2026-10-08 08:42:41','2026-10-08 08:42:41'),(1139,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 08:42:42','2026-10-08 08:42:42'),(1140,1,'INDEX','Report','Branch-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/reports/branch-wise?page=1&per_page=15','GET','2026-10-08 08:42:43','2026-10-08 08:42:43'),(1141,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 08:47:23','2026-10-08 08:47:23'),(1142,1,'INDEX','Report','Branch-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/reports/branch-wise?page=1&per_page=15','GET','2026-10-08 08:47:24','2026-10-08 08:47:24'),(1143,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 08:47:26','2026-10-08 08:47:26'),(1144,1,'INDEX','Report','Branch-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/reports/branch-wise?page=1&per_page=15','GET','2026-10-08 08:47:27','2026-10-08 08:47:27'),(1145,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 08:51:59','2026-10-08 08:51:59'),(1146,1,'INDEX','Report','Branch-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/reports/branch-wise?page=1&per_page=15','GET','2026-10-08 08:52:00','2026-10-08 08:52:00'),(1147,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 08:52:02','2026-10-08 08:52:02'),(1148,1,'INDEX','Report','Branch-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/reports/branch-wise?page=1&per_page=15','GET','2026-10-08 08:52:03','2026-10-08 08:52:03'),(1149,1,'INDEX','Report','Customer-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/reports/customer-wise?page=1&per_page=15','GET','2026-10-08 08:53:38','2026-10-08 08:53:38'),(1150,1,'INDEX','Report','Branch-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/reports/branch-wise?page=1&per_page=15','GET','2026-10-08 08:53:43','2026-10-08 08:53:43'),(1151,1,'INDEX','Report','Customer-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/reports/customer-wise?page=1&per_page=15','GET','2026-10-08 08:53:44','2026-10-08 08:53:44'),(1152,1,'INDEX','Report','Branch-wise report viewed','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/reports/branch-wise?page=1&per_page=15','GET','2026-10-08 08:53:47','2026-10-08 08:53:47'),(1153,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-10-08 08:53:56','2026-10-08 08:53:56'),(1154,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-10-08 08:53:58','2026-10-08 08:53:58'),(1155,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-10-08 08:54:01','2026-10-08 08:54:01'),(1156,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-10-08 08:54:03','2026-10-08 08:54:03'),(1157,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&page=1','GET','2026-10-08 09:01:26','2026-10-08 09:01:26'),(1158,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&page=1','GET','2026-10-08 09:01:28','2026-10-08 09:01:28'),(1159,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&page=1','GET','2026-10-08 09:02:14','2026-10-08 09:02:14'),(1160,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&page=1','GET','2026-10-08 09:02:18','2026-10-08 09:02:18'),(1161,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": {\"search\": \"APP-BRMAIN001-2609250001\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?per_page=100&search=APP-BRMAIN001-2609250001&status=submitted%2Creview_failed%2Creviewed%2Creverify%2Creopened','GET','2026-10-08 09:02:52','2026-10-08 09:02:52'),(1162,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-08 09:02:56','2026-10-08 09:02:56'),(1163,1,'INDEX','LoanApplicationGuarantor','Loan application guarantors index accessed','{\"count\": 2, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors?loan_application_id=1&page=1','GET','2026-10-08 09:02:58','2026-10-08 09:02:58'),(1164,1,'CREATE','Document','Uploaded document: Driving License','{\"remarks\": \"Application: APP-BRMAIN001-2609250001 | Customer: Piranya Paskaran\", \"file_path\": \"uploads/documents/Driving License.pdf\", \"customer_id\": \"2\", \"uploaded_at\": \"2026-10-08T09:03:22.399941Z\", \"uploaded_by\": 1, \"document_name\": \"Driving License\", \"document_type\": \"driving_license\", \"loan_application_id\": \"1\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-10-08 09:03:22','2026-10-08 09:03:22'),(1165,1,'SHOW','Document','Document file opened: uploads/documents/Driving License.pdf','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents/file?path=uploads%2Fdocuments%2FDriving%20License.pdf','GET','2026-10-08 09:03:27','2026-10-08 09:03:27'),(1166,1,'SHOW','Document','Document file opened: uploads/documents/Driving License.pdf','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents/file?path=uploads%2Fdocuments%2FDriving%20License.pdf','GET','2026-10-08 09:03:42','2026-10-08 09:03:42'),(1167,1,'DELETE','Document','Deleted document: Driving License','{\"deleted_by\": 1, \"document_id\": \"76\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents/76','DELETE','2026-10-08 09:11:03','2026-10-08 09:11:03'),(1168,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": {\"search\": \"APP-BRMAIN001-2609250001\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?per_page=100&search=APP-BRMAIN001-2609250001&status=submitted%2Creview_failed%2Creviewed%2Creverify%2Creopened','GET','2026-10-08 09:11:20','2026-10-08 09:11:20'),(1169,1,'INDEX','Document','Documents index accessed','{\"count\": 7, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-08 09:11:25','2026-10-08 09:11:25'),(1170,1,'INDEX','LoanApplicationGuarantor','Loan application guarantors index accessed','{\"count\": 2, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors?loan_application_id=1&page=1','GET','2026-10-08 09:11:27','2026-10-08 09:11:27'),(1171,1,'CREATE','Document','Uploaded document: Employment Confirmation Letter','{\"remarks\": \"Application: APP-BRMAIN001-2609250001 | Guarantor: Inshath\", \"file_path\": \"uploads/documents/Employment Confirmation Letter.pdf\", \"uploaded_at\": \"2026-10-08T09:11:58.697897Z\", \"uploaded_by\": 1, \"guarantor_id\": \"2\", \"document_name\": \"Employment Confirmation Letter\", \"document_type\": \"employer_letter\", \"loan_application_id\": \"1\"}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents','POST','2026-10-08 09:11:58','2026-10-08 09:11:58'),(1172,1,'SHOW','Document','Document file opened: uploads/documents/Employment Confirmation Letter.pdf','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/documents/file?path=uploads%2Fdocuments%2FEmployment%20Confirmation%20Letter.pdf','GET','2026-10-08 09:12:26','2026-10-08 09:12:26'),(1173,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 09:12:54','2026-10-08 09:12:54'),(1174,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 09:12:55','2026-10-08 09:12:55'),(1175,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 09:13:34','2026-10-08 09:13:34'),(1176,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 09:13:35','2026-10-08 09:13:35'),(1177,1,'INDEX','Document','Documents index accessed','{\"count\": 8, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&page=1','GET','2026-10-08 09:13:59','2026-10-08 09:13:59'),(1178,1,'INDEX','Document','Documents index accessed','{\"count\": 8, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&page=1','GET','2026-10-08 09:14:00','2026-10-08 09:14:00'),(1179,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": {\"search\": \"APP-BRMAIN001-2609250001\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?per_page=100&search=APP-BRMAIN001-2609250001&status=submitted%2Creview_failed%2Creviewed%2Creverify%2Creopened','GET','2026-10-08 09:14:28','2026-10-08 09:14:28'),(1180,1,'INDEX','Document','Documents index accessed','{\"count\": 8, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents?loan_application_id=1&per_page=200','GET','2026-10-08 09:14:33','2026-10-08 09:14:33'),(1181,1,'INDEX','LoanApplicationGuarantor','Loan application guarantors index accessed','{\"count\": 2, \"filters\": {\"loan_application_id\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-application-guarantors?loan_application_id=1&page=1','GET','2026-10-08 09:14:34','2026-10-08 09:14:34'),(1182,1,'SHOW','Document','Document file opened: uploads/documents/Employment Confirmation Letter.pdf','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents/file?path=uploads%2Fdocuments%2FEmployment%20Confirmation%20Letter.pdf','GET','2026-10-08 09:14:41','2026-10-08 09:14:41'),(1183,1,'SHOW','Document','Document file opened: uploads/documents/Employment Confirmation Letter.pdf','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/documents/file?path=uploads%2Fdocuments%2FEmployment%20Confirmation%20Letter.pdf','GET','2026-10-08 09:16:07','2026-10-08 09:16:07'),(1184,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 09:39:35','2026-10-08 09:39:35'),(1185,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 09:39:37','2026-10-08 09:39:37'),(1186,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 10:05:26','2026-10-08 10:05:26'),(1187,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 10:05:27','2026-10-08 10:05:27'),(1188,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 10:12:19','2026-10-08 10:12:19'),(1189,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 10:12:20','2026-10-08 10:12:20'),(1190,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 10:24:15','2026-10-08 10:24:15'),(1191,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 10:24:17','2026-10-08 10:24:17'),(1192,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 10:41:32','2026-10-08 10:41:32'),(1193,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 10:41:32','2026-10-08 10:41:32'),(1194,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 10:54:48','2026-10-08 10:54:48'),(1195,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 10:54:49','2026-10-08 10:54:49'),(1196,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?page=1&per_page=20','GET','2026-10-08 10:57:10','2026-10-08 10:57:10'),(1197,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 11:09:34','2026-10-08 11:09:34'),(1198,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 11:09:35','2026-10-08 11:09:35'),(1199,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-08 11:11:28','2026-10-08 11:11:28'),(1200,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-08 11:11:29','2026-10-08 11:11:29'),(1201,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 11:11:31','2026-10-08 11:11:31'),(1202,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 11:11:32','2026-10-08 11:11:32'),(1203,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-08 11:11:33','2026-10-08 11:11:33'),(1204,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-08 11:11:34','2026-10-08 11:11:34'),(1205,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?page=1&per_page=20','GET','2026-10-08 11:13:41','2026-10-08 11:13:41'),(1206,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?month=August%202026','GET','2026-10-08 11:14:45','2026-10-08 11:14:45'),(1207,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?month=August%202026','GET','2026-10-08 11:14:45','2026-10-08 11:14:45'),(1208,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?page=1&per_page=20','GET','2026-10-08 11:14:47','2026-10-08 11:14:47'),(1209,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-08 11:15:10','2026-10-08 11:15:10'),(1210,1,'INDEX','Customer','Customers index accessed','{\"count\": 3, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/customers?page=1','GET','2026-10-08 11:15:12','2026-10-08 11:15:12'),(1211,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?page=1&per_page=20','GET','2026-10-08 11:15:12','2026-10-08 11:15:12'),(1212,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?month=August%202026','GET','2026-10-08 11:15:26','2026-10-08 11:15:26'),(1213,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?month=August%202026','GET','2026-10-08 11:15:27','2026-10-08 11:15:27'),(1214,1,'INDEX','Payment','Payments index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/payments?page=1','GET','2026-10-08 11:15:41','2026-10-08 11:15:41'),(1215,1,'INDEX','Payment','Payments index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/payments?page=1','GET','2026-10-08 11:15:42','2026-10-08 11:15:42'),(1216,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-10-08 11:15:48','2026-10-08 11:15:48'),(1217,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-10-08 11:15:49','2026-10-08 11:15:49'),(1218,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-10-08 11:15:54','2026-10-08 11:15:54'),(1219,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-10-08 11:15:55','2026-10-08 11:15:55'),(1220,1,'INDEX','Payment','Payments index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/payments?page=1','GET','2026-10-08 11:15:59','2026-10-08 11:15:59'),(1221,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?month=August%202026','GET','2026-10-08 11:16:00','2026-10-08 11:16:00'),(1222,1,'INDEX','Payment','Payments index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/payments?page=1','GET','2026-10-08 11:16:01','2026-10-08 11:16:01'),(1223,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?month=August%202026','GET','2026-10-08 11:16:02','2026-10-08 11:16:02'),(1224,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-10-08 11:18:19','2026-10-08 11:18:19'),(1225,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-10-08 11:18:20','2026-10-08 11:18:20'),(1226,1,'INDEX','RecoveryCase','Recovery cases index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/recovery-cases?page=1','GET','2026-10-08 11:18:23','2026-10-08 11:18:23'),(1227,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1&per_page=1000','GET','2026-10-08 11:18:26','2026-10-08 11:18:26'),(1228,1,'INDEX','Payment','Payments index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/payments?page=1','GET','2026-10-08 11:18:28','2026-10-08 11:18:28'),(1229,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?month=August%202026','GET','2026-10-08 11:18:29','2026-10-08 11:18:29'),(1230,1,'INDEX','Payment','Payments index accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/payments?page=1','GET','2026-10-08 11:18:30','2026-10-08 11:18:30'),(1231,1,'INDEX','LoanInstallment','Loan installments list accessed','{\"count\": 0, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-installments/list?month=August%202026','GET','2026-10-08 11:18:31','2026-10-08 11:18:31'),(1232,1,'INDEX','LegalDocument','Legal documents index accessed','{\"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/legal-documents?page=1&per_page=20','GET','2026-10-08 11:18:43','2026-10-08 11:18:43'),(1233,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 11:18:54','2026-10-08 11:18:54'),(1234,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 11:18:55','2026-10-08 11:18:55'),(1235,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 11:29:11','2026-10-08 11:29:11'),(1236,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 11:29:12','2026-10-08 11:29:12'),(1237,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-08 11:29:13','2026-10-08 11:29:13'),(1238,1,'INDEX','LoanApplication','Loan applications index accessed','{\"count\": 1, \"filters\": [], \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Mobile Safari/537.36','http://127.0.0.1:8000/api/v1/loan-applications?page=1','GET','2026-10-08 11:29:14','2026-10-08 11:29:14'),(1239,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-09 04:04:37','2026-10-09 04:04:37'),(1240,1,'INDEX','AdminDashboard','Admin viewed dashboard overview','{\"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/admin-dashboard/overview?end_date=2026-10-09&start_date=2026-10-01','GET','2026-10-09 04:04:38','2026-10-09 04:04:38'),(1241,1,'INDEX','Notification','Notifications index accessed','{\"count\": 0, \"filters\": {\"mine\": \"1\"}, \"user_id\": 1}','info','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','http://127.0.0.1:8000/api/v1/notifications?mine=1&per_page=10','GET','2026-10-09 04:04:39','2026-10-09 04:04:39');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `application_history`
--

DROP TABLE IF EXISTS `application_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `application_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `basic_salary` decimal(15,2) DEFAULT NULL,
  `fixed_allowances` decimal(15,2) DEFAULT NULL,
  `other_allowances` decimal(15,2) DEFAULT NULL,
  `other_income` decimal(15,2) DEFAULT NULL,
  `total_monthly_income` decimal(15,2) DEFAULT NULL,
  `household_expenses` decimal(15,2) DEFAULT NULL,
  `rent_expense` decimal(15,2) DEFAULT NULL,
  `insurance_premiums` decimal(15,2) DEFAULT NULL,
  `other_expenses` decimal(15,2) DEFAULT NULL,
  `total_monthly_expenses` decimal(15,2) DEFAULT NULL,
  `requested_amount` decimal(15,2) DEFAULT NULL,
  `purpose` text COLLATE utf8mb4_unicode_ci,
  `recorded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application_history_application_id_foreign` (`application_id`),
  KEY `application_history_customer_id_foreign` (`customer_id`),
  CONSTRAINT `application_history_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_history_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_history`
--

LOCK TABLES `application_history` WRITE;
/*!40000 ALTER TABLE `application_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `application_history` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applications`
--

LOCK TABLES `applications` WRITE;
/*!40000 ALTER TABLE `applications` DISABLE KEYS */;
INSERT INTO `applications` VALUES (1,'APP-BRMAIN001-2609250001','loan','Head Office',NULL,500000.00,NULL,12,NULL,'in_progress','2026-09-25 09:42:24','2026-10-08 05:48:41');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,NULL,1,1,1,'Head Office','HCOL','No. 10, Galle Road',NULL,'Colombo','00300','+94 11 000 0000',NULL,'headoffice@cdpcapital.lk',NULL,'2026-09-23','main',6.92710000,79.86120000,1,1,'2026-09-25 06:28:50','2026-09-25 09:46:50'),(2,NULL,1,1,1,'Nugekoda','NUG','12, Main rd',NULL,'Colombo',NULL,'0752932640',NULL,NULL,NULL,'2026-09-17','city',NULL,NULL,1,0,'2026-09-25 07:48:34','2026-09-25 07:48:34');
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
INSERT INTO `cache` VALUES ('cdp-credix-cache-0Uv5Ha9KNPMMVHlP','a:1:{s:11:\"valid_until\";i:1790571329;}',1793163389),('cdp-credix-cache-6jPDFVjkiarFCgUk','a:1:{s:11:\"valid_until\";i:1790571259;}',1793161999),('cdp-credix-cache-94d92f976fd06fd3e8cf53ec4e03d646','i:1;',1791518734),('cdp-credix-cache-94d92f976fd06fd3e8cf53ec4e03d646:timer','i:1791518734;',1791518734),('cdp-credix-cache-9facce207cfe500b952220711d53629a','i:1;',1791447663),('cdp-credix-cache-9facce207cfe500b952220711d53629a:timer','i:1791447663;',1791447663),('cdp-credix-cache-cdea0c6cf988a96b71ac5ce10d938e62','i:1;',1791447648),('cdp-credix-cache-cdea0c6cf988a96b71ac5ce10d938e62:timer','i:1791447648;',1791447648),('cdp-credix-cache-dialog_sms_token','s:341:\"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MTcwODcsInVzZXJuYW1lIjoiQ0RQRVBWVExURCIsIm1vYmlsZSI6NzQ0MTIzMDk5LCJlbWFpbCI6IklUX0Rldk9wc0BjZHAubGsiLCJjdXN0b21lcl9yb2xlIjowLCJpc19hdXRob3JpemVkX3RvX3VzZV91aSI6dHJ1ZSwiaXNDYW1wYWlnbkFwcHJvdmFsRW5hYmxlZCI6MCwiaWF0IjoxNzkxNDQ3MTMwLCJleHAiOjE3OTE0OTAzMzB9.zYY1aUQ2O6a-hEtx0votZdYmdx6zgXBdLsN61yAMkIg\";',1791490328),('cdp-credix-cache-EOchcHMzI0guzcJ2','a:1:{s:11:\"valid_until\";i:1790323008;}',1792912668),('cdp-credix-cache-h6baeXlmGtVDoU5e','a:1:{s:11:\"valid_until\";i:1790571290;}',1793163350),('cdp-credix-cache-i2jKcSuJoLAT9PkU','a:1:{s:11:\"valid_until\";i:1790571381;}',1793163441),('cdp-credix-cache-JAEJ2bljP1m4y2Uu','a:1:{s:11:\"valid_until\";i:1790329663;}',1792915603),('cdp-credix-cache-login:nithushan|127.0.0.1','i:1;',1790572325),('cdp-credix-cache-login:nithushan|127.0.0.1:timer','i:1790572325;',1790572325),('cdp-credix-cache-login:sulani|127.0.0.1','i:3;',1790330569),('cdp-credix-cache-login:sulani|127.0.0.1:timer','i:1790330569;',1790330569),('cdp-credix-cache-login:test_username|127.0.0.1','i:1;',1790572558),('cdp-credix-cache-login:test_username|127.0.0.1:timer','i:1790572558;',1790572558),('cdp-credix-cache-MzcQae2gfNir0Gj5','a:1:{s:11:\"valid_until\";i:1790573899;}',1793165959),('cdp-credix-cache-QWZ1hxiKeCmlk1dP','a:1:{s:11:\"valid_until\";i:1790575600;}',1793165260),('cdp-credix-cache-R3l3WMjPUyUSXf3z','a:1:{s:11:\"valid_until\";i:1790323031;}',1792915091),('cdp-credix-cache-setting:cdp_investment_max_loan_percentage','d:50;',2106541819),('cdp-credix-cache-setting:credit_score_count_unpaid_overdue','b:1;',2106809625),('cdp-credix-cache-setting:credit_score_enabled','b:1;',2105680616),('cdp-credix-cache-setting:credit_score_grace_days','i:0;',2106809625),('cdp-credix-cache-setting:credit_score_late_penalty_points','i:10;',2106809625),('cdp-credix-cache-setting:credit_score_on_time_points','i:10;',2106809625),('cdp-credix-cache-setting:loan_approval_segregation_enabled','b:1;',2105680616),('cdp-credix-cache-setting:loan_revision_enabled','b:1;',2105680616),('cdp-credix-cache-setting:max_loans_per_guarantor','i:1;',2105689345),('cdp-credix-cache-setting:property_mortgage_max_loan_percentage','d:70;',2106548550),('cdp-credix-cache-setting:recovery_escalation_enabled','b:1;',2105680616),('cdp-credix-cache-setting:sms_notifications_enabled','b:1;',2105680616),('cdp-credix-cache-setting:vehicle_max_loan_percentage','d:70;',2106548550),('cdp-credix-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:6:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:10:\"group_name\";s:1:\"c\";s:4:\"name\";s:1:\"d\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";s:1:\"j\";s:12:\"is_protected\";}s:11:\"permissions\";a:225:{i:0;a:5:{s:1:\"a\";i:1;s:1:\"b\";s:31:\"Activity Management Permissions\";s:1:\"c\";s:14:\"Activity Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:1;a:5:{s:1:\"a\";i:2;s:1:\"b\";s:31:\"Activity Management Permissions\";s:1:\"c\";s:13:\"Activity Show\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:2;a:5:{s:1:\"a\";i:3;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:16:\"Permission Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:3;a:5:{s:1:\"a\";i:4;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:17:\"Permission Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:4;a:5:{s:1:\"a\";i:5;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:17:\"Permission Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:5;a:5:{s:1:\"a\";i:6;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:17:\"Permission Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:6;a:5:{s:1:\"a\";i:7;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:10:\"Role Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:7;a:5:{s:1:\"a\";i:8;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:11:\"Role Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:8;a:5:{s:1:\"a\";i:9;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:11:\"Role Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:9;a:5:{s:1:\"a\";i:10;s:1:\"b\";s:29:\"Access Management Permissions\";s:1:\"c\";s:11:\"Role Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:10;a:5:{s:1:\"a\";i:11;s:1:\"b\";s:27:\"User Management Permissions\";s:1:\"c\";s:10:\"User Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:11;a:5:{s:1:\"a\";i:12;s:1:\"b\";s:27:\"User Management Permissions\";s:1:\"c\";s:11:\"User Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:12;a:5:{s:1:\"a\";i:13;s:1:\"b\";s:27:\"User Management Permissions\";s:1:\"c\";s:11:\"User Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:13;a:5:{s:1:\"a\";i:14;s:1:\"b\";s:27:\"User Management Permissions\";s:1:\"c\";s:11:\"User Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:14;a:5:{s:1:\"a\";i:15;s:1:\"b\";s:27:\"User Management Permissions\";s:1:\"c\";s:18:\"User Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:15;a:5:{s:1:\"a\";i:16;s:1:\"b\";s:31:\"Employee Management Permissions\";s:1:\"c\";s:14:\"Employee Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:16;a:5:{s:1:\"a\";i:17;s:1:\"b\";s:30:\"Country Management Permissions\";s:1:\"c\";s:13:\"Country Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:17;a:5:{s:1:\"a\";i:18;s:1:\"b\";s:30:\"Country Management Permissions\";s:1:\"c\";s:14:\"Country Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:18;a:5:{s:1:\"a\";i:19;s:1:\"b\";s:30:\"Country Management Permissions\";s:1:\"c\";s:14:\"Country Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:19;a:5:{s:1:\"a\";i:20;s:1:\"b\";s:30:\"Country Management Permissions\";s:1:\"c\";s:14:\"Country Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:20;a:5:{s:1:\"a\";i:21;s:1:\"b\";s:30:\"Country Management Permissions\";s:1:\"c\";s:21:\"Country Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:21;a:5:{s:1:\"a\";i:22;s:1:\"b\";s:31:\"Province Management Permissions\";s:1:\"c\";s:14:\"Province Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:22;a:5:{s:1:\"a\";i:23;s:1:\"b\";s:31:\"Province Management Permissions\";s:1:\"c\";s:15:\"Province Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:23;a:5:{s:1:\"a\";i:24;s:1:\"b\";s:31:\"Province Management Permissions\";s:1:\"c\";s:15:\"Province Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:24;a:5:{s:1:\"a\";i:25;s:1:\"b\";s:31:\"Province Management Permissions\";s:1:\"c\";s:15:\"Province Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:25;a:5:{s:1:\"a\";i:26;s:1:\"b\";s:31:\"Province Management Permissions\";s:1:\"c\";s:22:\"Province Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:26;a:5:{s:1:\"a\";i:27;s:1:\"b\";s:28:\"Zonal Management Permissions\";s:1:\"c\";s:11:\"Zonal Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:27;a:5:{s:1:\"a\";i:28;s:1:\"b\";s:28:\"Zonal Management Permissions\";s:1:\"c\";s:12:\"Zonal Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:28;a:5:{s:1:\"a\";i:29;s:1:\"b\";s:28:\"Zonal Management Permissions\";s:1:\"c\";s:12:\"Zonal Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:29;a:5:{s:1:\"a\";i:30;s:1:\"b\";s:28:\"Zonal Management Permissions\";s:1:\"c\";s:12:\"Zonal Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:30;a:5:{s:1:\"a\";i:31;s:1:\"b\";s:28:\"Zonal Management Permissions\";s:1:\"c\";s:19:\"Zonal Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:31;a:5:{s:1:\"a\";i:32;s:1:\"b\";s:29:\"Region Management Permissions\";s:1:\"c\";s:12:\"Region Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:32;a:5:{s:1:\"a\";i:33;s:1:\"b\";s:29:\"Region Management Permissions\";s:1:\"c\";s:13:\"Region Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:33;a:5:{s:1:\"a\";i:34;s:1:\"b\";s:29:\"Region Management Permissions\";s:1:\"c\";s:13:\"Region Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:34;a:5:{s:1:\"a\";i:35;s:1:\"b\";s:29:\"Region Management Permissions\";s:1:\"c\";s:13:\"Region Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:35;a:5:{s:1:\"a\";i:36;s:1:\"b\";s:29:\"Region Management Permissions\";s:1:\"c\";s:20:\"Region Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:36;a:5:{s:1:\"a\";i:37;s:1:\"b\";s:29:\"Branch Management Permissions\";s:1:\"c\";s:12:\"Branch Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:37;a:5:{s:1:\"a\";i:38;s:1:\"b\";s:29:\"Branch Management Permissions\";s:1:\"c\";s:13:\"Branch Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:38;a:5:{s:1:\"a\";i:39;s:1:\"b\";s:29:\"Branch Management Permissions\";s:1:\"c\";s:13:\"Branch Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:39;a:5:{s:1:\"a\";i:40;s:1:\"b\";s:29:\"Branch Management Permissions\";s:1:\"c\";s:13:\"Branch Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:40;a:5:{s:1:\"a\";i:41;s:1:\"b\";s:29:\"Branch Management Permissions\";s:1:\"c\";s:20:\"Branch Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:41;a:5:{s:1:\"a\";i:42;s:1:\"b\";s:33:\"Department Management Permissions\";s:1:\"c\";s:16:\"Department Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:42;a:5:{s:1:\"a\";i:43;s:1:\"b\";s:33:\"Department Management Permissions\";s:1:\"c\";s:17:\"Department Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:43;a:5:{s:1:\"a\";i:44;s:1:\"b\";s:33:\"Department Management Permissions\";s:1:\"c\";s:17:\"Department Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:44;a:5:{s:1:\"a\";i:45;s:1:\"b\";s:33:\"Department Management Permissions\";s:1:\"c\";s:17:\"Department Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:45;a:5:{s:1:\"a\";i:46;s:1:\"b\";s:33:\"Department Management Permissions\";s:1:\"c\";s:24:\"Department Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:46;a:5:{s:1:\"a\";i:47;s:1:\"b\";s:34:\"Designation Management Permissions\";s:1:\"c\";s:17:\"Designation Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:47;a:5:{s:1:\"a\";i:48;s:1:\"b\";s:34:\"Designation Management Permissions\";s:1:\"c\";s:18:\"Designation Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:48;a:5:{s:1:\"a\";i:49;s:1:\"b\";s:34:\"Designation Management Permissions\";s:1:\"c\";s:18:\"Designation Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:49;a:5:{s:1:\"a\";i:50;s:1:\"b\";s:34:\"Designation Management Permissions\";s:1:\"c\";s:18:\"Designation Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:50;a:5:{s:1:\"a\";i:51;s:1:\"b\";s:34:\"Designation Management Permissions\";s:1:\"c\";s:25:\"Designation Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:51;a:5:{s:1:\"a\";i:52;s:1:\"b\";s:28:\"Group Management Permissions\";s:1:\"c\";s:11:\"Group Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:52;a:5:{s:1:\"a\";i:53;s:1:\"b\";s:28:\"Group Management Permissions\";s:1:\"c\";s:12:\"Group Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:53;a:5:{s:1:\"a\";i:54;s:1:\"b\";s:28:\"Group Management Permissions\";s:1:\"c\";s:12:\"Group Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:54;a:5:{s:1:\"a\";i:55;s:1:\"b\";s:28:\"Group Management Permissions\";s:1:\"c\";s:12:\"Group Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:55;a:5:{s:1:\"a\";i:56;s:1:\"b\";s:28:\"Group Management Permissions\";s:1:\"c\";s:19:\"Group Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:56;a:5:{s:1:\"a\";i:57;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:14:\"Customer Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:57;a:5:{s:1:\"a\";i:58;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:15:\"Customer Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:58;a:5:{s:1:\"a\";i:59;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:15:\"Customer Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:59;a:5:{s:1:\"a\";i:60;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:15:\"Customer Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:60;a:5:{s:1:\"a\";i:61;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:16:\"Customer Restore\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:61;a:5:{s:1:\"a\";i:62;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:21:\"Customer Force Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:62;a:5:{s:1:\"a\";i:63;s:1:\"b\";s:31:\"Customer Management Permissions\";s:1:\"c\";s:22:\"Customer Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:63;a:5:{s:1:\"a\";i:64;s:1:\"b\";s:43:\"Customer Bank Detail Management Permissions\";s:1:\"c\";s:26:\"Customer Bank Detail Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:64;a:5:{s:1:\"a\";i:65;s:1:\"b\";s:43:\"Customer Bank Detail Management Permissions\";s:1:\"c\";s:27:\"Customer Bank Detail Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:65;a:5:{s:1:\"a\";i:66;s:1:\"b\";s:43:\"Customer Bank Detail Management Permissions\";s:1:\"c\";s:27:\"Customer Bank Detail Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:66;a:5:{s:1:\"a\";i:67;s:1:\"b\";s:43:\"Customer Bank Detail Management Permissions\";s:1:\"c\";s:27:\"Customer Bank Detail Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:67;a:5:{s:1:\"a\";i:68;s:1:\"b\";s:43:\"Customer Bank Detail Management Permissions\";s:1:\"c\";s:34:\"Customer Bank Detail Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:68;a:5:{s:1:\"a\";i:69;s:1:\"b\";s:32:\"Guarantor Management Permissions\";s:1:\"c\";s:15:\"Guarantor Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:69;a:5:{s:1:\"a\";i:70;s:1:\"b\";s:32:\"Guarantor Management Permissions\";s:1:\"c\";s:16:\"Guarantor Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:70;a:5:{s:1:\"a\";i:71;s:1:\"b\";s:32:\"Guarantor Management Permissions\";s:1:\"c\";s:16:\"Guarantor Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:71;a:5:{s:1:\"a\";i:72;s:1:\"b\";s:32:\"Guarantor Management Permissions\";s:1:\"c\";s:16:\"Guarantor Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:72;a:5:{s:1:\"a\";i:73;s:1:\"b\";s:34:\"Application Management Permissions\";s:1:\"c\";s:17:\"Application Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:73;a:5:{s:1:\"a\";i:74;s:1:\"b\";s:34:\"Application Management Permissions\";s:1:\"c\";s:18:\"Application Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:74;a:5:{s:1:\"a\";i:75;s:1:\"b\";s:34:\"Application Management Permissions\";s:1:\"c\";s:18:\"Application Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:75;a:5:{s:1:\"a\";i:76;s:1:\"b\";s:34:\"Application Management Permissions\";s:1:\"c\";s:18:\"Application Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:76;a:5:{s:1:\"a\";i:77;s:1:\"b\";s:42:\"Application History Management Permissions\";s:1:\"c\";s:25:\"Application History Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:77;a:5:{s:1:\"a\";i:78;s:1:\"b\";s:42:\"Application History Management Permissions\";s:1:\"c\";s:26:\"Application History Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:78;a:5:{s:1:\"a\";i:79;s:1:\"b\";s:42:\"Application History Management Permissions\";s:1:\"c\";s:26:\"Application History Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:79;a:5:{s:1:\"a\";i:80;s:1:\"b\";s:42:\"Application History Management Permissions\";s:1:\"c\";s:26:\"Application History Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:80;a:5:{s:1:\"a\";i:81;s:1:\"b\";s:34:\"Fixed Asset Management Permissions\";s:1:\"c\";s:17:\"Fixed Asset Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:81;a:5:{s:1:\"a\";i:82;s:1:\"b\";s:34:\"Fixed Asset Management Permissions\";s:1:\"c\";s:18:\"Fixed Asset Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:82;a:5:{s:1:\"a\";i:83;s:1:\"b\";s:34:\"Fixed Asset Management Permissions\";s:1:\"c\";s:18:\"Fixed Asset Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:83;a:5:{s:1:\"a\";i:84;s:1:\"b\";s:34:\"Fixed Asset Management Permissions\";s:1:\"c\";s:18:\"Fixed Asset Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:84;a:5:{s:1:\"a\";i:85;s:1:\"b\";s:35:\"Moving Asset Management Permissions\";s:1:\"c\";s:18:\"Moving Asset Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:85;a:5:{s:1:\"a\";i:86;s:1:\"b\";s:35:\"Moving Asset Management Permissions\";s:1:\"c\";s:19:\"Moving Asset Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:86;a:5:{s:1:\"a\";i:87;s:1:\"b\";s:35:\"Moving Asset Management Permissions\";s:1:\"c\";s:19:\"Moving Asset Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:87;a:5:{s:1:\"a\";i:88;s:1:\"b\";s:35:\"Moving Asset Management Permissions\";s:1:\"c\";s:19:\"Moving Asset Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:88;a:5:{s:1:\"a\";i:89;s:1:\"b\";s:32:\"Liability Management Permissions\";s:1:\"c\";s:15:\"Liability Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:89;a:5:{s:1:\"a\";i:90;s:1:\"b\";s:32:\"Liability Management Permissions\";s:1:\"c\";s:16:\"Liability Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:90;a:5:{s:1:\"a\";i:91;s:1:\"b\";s:32:\"Liability Management Permissions\";s:1:\"c\";s:16:\"Liability Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:91;a:5:{s:1:\"a\";i:92;s:1:\"b\";s:32:\"Liability Management Permissions\";s:1:\"c\";s:16:\"Liability Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:92;a:5:{s:1:\"a\";i:93;s:1:\"b\";s:32:\"Liability Management Permissions\";s:1:\"c\";s:23:\"Liability Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:93;a:5:{s:1:\"a\";i:94;s:1:\"b\";s:31:\"Document Management Permissions\";s:1:\"c\";s:14:\"Document Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:94;a:5:{s:1:\"a\";i:95;s:1:\"b\";s:31:\"Document Management Permissions\";s:1:\"c\";s:15:\"Document Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:95;a:5:{s:1:\"a\";i:96;s:1:\"b\";s:31:\"Document Management Permissions\";s:1:\"c\";s:15:\"Document Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:96;a:5:{s:1:\"a\";i:97;s:1:\"b\";s:31:\"Document Management Permissions\";s:1:\"c\";s:15:\"Document Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:97;a:5:{s:1:\"a\";i:98;s:1:\"b\";s:32:\"Loan Term Management Permissions\";s:1:\"c\";s:15:\"Loan Term Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:98;a:5:{s:1:\"a\";i:99;s:1:\"b\";s:32:\"Loan Term Management Permissions\";s:1:\"c\";s:16:\"Loan Term Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:99;a:5:{s:1:\"a\";i:100;s:1:\"b\";s:32:\"Loan Term Management Permissions\";s:1:\"c\";s:16:\"Loan Term Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:100;a:5:{s:1:\"a\";i:101;s:1:\"b\";s:32:\"Loan Term Management Permissions\";s:1:\"c\";s:16:\"Loan Term Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:101;a:5:{s:1:\"a\";i:102;s:1:\"b\";s:32:\"Loan Term Management Permissions\";s:1:\"c\";s:23:\"Loan Term Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:102;a:5:{s:1:\"a\";i:103;s:1:\"b\";s:32:\"Loan Type Management Permissions\";s:1:\"c\";s:15:\"Loan Type Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:103;a:5:{s:1:\"a\";i:104;s:1:\"b\";s:32:\"Loan Type Management Permissions\";s:1:\"c\";s:16:\"Loan Type Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:104;a:5:{s:1:\"a\";i:105;s:1:\"b\";s:32:\"Loan Type Management Permissions\";s:1:\"c\";s:16:\"Loan Type Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:105;a:5:{s:1:\"a\";i:106;s:1:\"b\";s:32:\"Loan Type Management Permissions\";s:1:\"c\";s:16:\"Loan Type Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:106;a:5:{s:1:\"a\";i:107;s:1:\"b\";s:32:\"Loan Type Management Permissions\";s:1:\"c\";s:23:\"Loan Type Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:107;a:5:{s:1:\"a\";i:108;s:1:\"b\";s:35:\"Loan Product Management Permissions\";s:1:\"c\";s:18:\"Loan Product Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:108;a:5:{s:1:\"a\";i:109;s:1:\"b\";s:35:\"Loan Product Management Permissions\";s:1:\"c\";s:19:\"Loan Product Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:109;a:5:{s:1:\"a\";i:110;s:1:\"b\";s:35:\"Loan Product Management Permissions\";s:1:\"c\";s:19:\"Loan Product Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:110;a:5:{s:1:\"a\";i:111;s:1:\"b\";s:35:\"Loan Product Management Permissions\";s:1:\"c\";s:19:\"Loan Product Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:111;a:5:{s:1:\"a\";i:112;s:1:\"b\";s:35:\"Loan Product Management Permissions\";s:1:\"c\";s:26:\"Loan Product Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:112;a:5:{s:1:\"a\";i:113;s:1:\"b\";s:25:\"Global Search Permissions\";s:1:\"c\";s:13:\"Global Search\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:113;a:5:{s:1:\"a\";i:114;s:1:\"b\";s:23:\"CDP Connect Permissions\";s:1:\"c\";s:25:\"CDP Customer Verification\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:114;a:5:{s:1:\"a\";i:115;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:22:\"Loan Application Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:115;a:5:{s:1:\"a\";i:116;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:116;a:5:{s:1:\"a\";i:117;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:117;a:5:{s:1:\"a\";i:118;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:118;a:5:{s:1:\"a\";i:119;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:30:\"Loan Application Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:119;a:5:{s:1:\"a\";i:120;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Review\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:120;a:5:{s:1:\"a\";i:121;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:25:\"Loan Application Resubmit\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:121;a:5:{s:1:\"a\";i:122;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Verify\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:122;a:5:{s:1:\"a\";i:123;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:25:\"Loan Application Reverify\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:123;a:5:{s:1:\"a\";i:124;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:24:\"Loan Application Approve\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:124;a:5:{s:1:\"a\";i:125;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Reject\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:125;a:5:{s:1:\"a\";i:126;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Reopen\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:126;a:5:{s:1:\"a\";i:127;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:31:\"Loan Application Offer Response\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:127;a:5:{s:1:\"a\";i:128;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:37:\"Loan Application Status History Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:128;a:5:{s:1:\"a\";i:129;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:20:\"Legal Template Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:129;a:5:{s:1:\"a\";i:130;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:21:\"Legal Template Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:130;a:5:{s:1:\"a\";i:131;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:21:\"Legal Template Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:131;a:5:{s:1:\"a\";i:132;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:21:\"Legal Template Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:132;a:5:{s:1:\"a\";i:133;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:28:\"Legal Template Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:133;a:5:{s:1:\"a\";i:134;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:20:\"Legal Document Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:134;a:5:{s:1:\"a\";i:135;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:21:\"Legal Document Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:135;a:5:{s:1:\"a\";i:136;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:21:\"Legal Document Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:136;a:5:{s:1:\"a\";i:137;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:21:\"Legal Document Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:137;a:5:{s:1:\"a\";i:138;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:28:\"Legal Document Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:138;a:5:{s:1:\"a\";i:139;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:25:\"Loan Application Disburse\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:139;a:5:{s:1:\"a\";i:140;s:1:\"b\";s:39:\"Loan Application Management Permissions\";s:1:\"c\";s:23:\"Loan Application Cancel\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:140;a:5:{s:1:\"a\";i:141;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:16:\"Group Loan Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:141;a:5:{s:1:\"a\";i:142;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:142;a:5:{s:1:\"a\";i:143;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:143;a:5:{s:1:\"a\";i:144;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:144;a:5:{s:1:\"a\";i:145;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:24:\"Group Loan Toggle Status\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:145;a:5:{s:1:\"a\";i:146;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Review\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:146;a:5:{s:1:\"a\";i:147;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:19:\"Group Loan Resubmit\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:147;a:5:{s:1:\"a\";i:148;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Verify\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:148;a:5:{s:1:\"a\";i:149;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:19:\"Group Loan Reverify\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:149;a:5:{s:1:\"a\";i:150;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:18:\"Group Loan Approve\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:150;a:5:{s:1:\"a\";i:151;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Reject\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:151;a:5:{s:1:\"a\";i:152;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Reopen\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:152;a:5:{s:1:\"a\";i:153;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:25:\"Group Loan Offer Response\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:153;a:5:{s:1:\"a\";i:154;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:19:\"Group Loan Disburse\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:154;a:5:{s:1:\"a\";i:155;s:1:\"b\";s:33:\"Group Loan Management Permissions\";s:1:\"c\";s:17:\"Group Loan Cancel\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:155;a:5:{s:1:\"a\";i:156;s:1:\"b\";s:38:\"Group Loan Item Management Permissions\";s:1:\"c\";s:21:\"Group Loan Item Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:156;a:5:{s:1:\"a\";i:157;s:1:\"b\";s:38:\"Group Loan Item Management Permissions\";s:1:\"c\";s:22:\"Group Loan Item Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:157;a:5:{s:1:\"a\";i:158;s:1:\"b\";s:38:\"Group Loan Item Management Permissions\";s:1:\"c\";s:22:\"Group Loan Item Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:158;a:5:{s:1:\"a\";i:159;s:1:\"b\";s:36:\"Loan Revision Management Permissions\";s:1:\"c\";s:19:\"Loan Revision Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:159;a:5:{s:1:\"a\";i:160;s:1:\"b\";s:36:\"Loan Revision Management Permissions\";s:1:\"c\";s:20:\"Loan Revision Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:160;a:5:{s:1:\"a\";i:161;s:1:\"b\";s:36:\"Loan Revision Management Permissions\";s:1:\"c\";s:21:\"Loan Revision Approve\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:161;a:5:{s:1:\"a\";i:162;s:1:\"b\";s:36:\"Loan Revision Management Permissions\";s:1:\"c\";s:20:\"Loan Revision Reject\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:162;a:5:{s:1:\"a\";i:163;s:1:\"b\";s:36:\"Loan Revision Management Permissions\";s:1:\"c\";s:20:\"Loan Revision Cancel\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:163;a:5:{s:1:\"a\";i:164;s:1:\"b\";s:49:\"Loan Application Guarantor Management Permissions\";s:1:\"c\";s:32:\"Loan Application Guarantor Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:164;a:5:{s:1:\"a\";i:165;s:1:\"b\";s:49:\"Loan Application Guarantor Management Permissions\";s:1:\"c\";s:33:\"Loan Application Guarantor Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:165;a:5:{s:1:\"a\";i:166;s:1:\"b\";s:49:\"Loan Application Guarantor Management Permissions\";s:1:\"c\";s:33:\"Loan Application Guarantor Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:166;a:5:{s:1:\"a\";i:167;s:1:\"b\";s:49:\"Loan Application Guarantor Management Permissions\";s:1:\"c\";s:33:\"Loan Application Guarantor Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:167;a:5:{s:1:\"a\";i:168;s:1:\"b\";s:48:\"Loan Application Customer Management Permissions\";s:1:\"c\";s:31:\"Loan Application Customer Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:168;a:5:{s:1:\"a\";i:169;s:1:\"b\";s:48:\"Loan Application Customer Management Permissions\";s:1:\"c\";s:32:\"Loan Application Customer Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:169;a:5:{s:1:\"a\";i:170;s:1:\"b\";s:48:\"Loan Application Customer Management Permissions\";s:1:\"c\";s:32:\"Loan Application Customer Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:170;a:5:{s:1:\"a\";i:171;s:1:\"b\";s:48:\"Loan Application Customer Management Permissions\";s:1:\"c\";s:32:\"Loan Application Customer Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:171;a:5:{s:1:\"a\";i:172;s:1:\"b\";s:51:\"Loan Application Fixed Asset Management Permissions\";s:1:\"c\";s:34:\"Loan Application Fixed Asset Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:172;a:5:{s:1:\"a\";i:173;s:1:\"b\";s:51:\"Loan Application Fixed Asset Management Permissions\";s:1:\"c\";s:35:\"Loan Application Fixed Asset Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:173;a:5:{s:1:\"a\";i:174;s:1:\"b\";s:51:\"Loan Application Fixed Asset Management Permissions\";s:1:\"c\";s:35:\"Loan Application Fixed Asset Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:174;a:5:{s:1:\"a\";i:175;s:1:\"b\";s:52:\"Loan Application Moving Asset Management Permissions\";s:1:\"c\";s:35:\"Loan Application Moving Asset Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:175;a:5:{s:1:\"a\";i:176;s:1:\"b\";s:52:\"Loan Application Moving Asset Management Permissions\";s:1:\"c\";s:36:\"Loan Application Moving Asset Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:176;a:5:{s:1:\"a\";i:177;s:1:\"b\";s:52:\"Loan Application Moving Asset Management Permissions\";s:1:\"c\";s:36:\"Loan Application Moving Asset Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:177;a:5:{s:1:\"a\";i:178;s:1:\"b\";s:49:\"Loan Application Liability Management Permissions\";s:1:\"c\";s:32:\"Loan Application Liability Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:178;a:5:{s:1:\"a\";i:179;s:1:\"b\";s:49:\"Loan Application Liability Management Permissions\";s:1:\"c\";s:33:\"Loan Application Liability Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:179;a:5:{s:1:\"a\";i:180;s:1:\"b\";s:49:\"Loan Application Liability Management Permissions\";s:1:\"c\";s:33:\"Loan Application Liability Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:180;a:5:{s:1:\"a\";i:181;s:1:\"b\";s:51:\"Loan Application Bank Detail Management Permissions\";s:1:\"c\";s:34:\"Loan Application Bank Detail Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:181;a:5:{s:1:\"a\";i:182;s:1:\"b\";s:51:\"Loan Application Bank Detail Management Permissions\";s:1:\"c\";s:35:\"Loan Application Bank Detail Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:182;a:5:{s:1:\"a\";i:183;s:1:\"b\";s:51:\"Loan Application Bank Detail Management Permissions\";s:1:\"c\";s:35:\"Loan Application Bank Detail Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:183;a:5:{s:1:\"a\";i:184;s:1:\"b\";s:39:\"Loan Installment Management Permissions\";s:1:\"c\";s:22:\"Loan Installment Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:184;a:5:{s:1:\"a\";i:185;s:1:\"b\";s:39:\"Loan Installment Management Permissions\";s:1:\"c\";s:23:\"Loan Installment Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:185;a:5:{s:1:\"a\";i:186;s:1:\"b\";s:39:\"Loan Installment Management Permissions\";s:1:\"c\";s:23:\"Loan Installment Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:186;a:5:{s:1:\"a\";i:187;s:1:\"b\";s:39:\"Loan Installment Management Permissions\";s:1:\"c\";s:23:\"Loan Installment Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:187;a:5:{s:1:\"a\";i:188;s:1:\"b\";s:30:\"Payment Management Permissions\";s:1:\"c\";s:13:\"Payment Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:188;a:5:{s:1:\"a\";i:189;s:1:\"b\";s:30:\"Payment Management Permissions\";s:1:\"c\";s:14:\"Payment Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:189;a:5:{s:1:\"a\";i:190;s:1:\"b\";s:30:\"Payment Management Permissions\";s:1:\"c\";s:14:\"Payment Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:190;a:5:{s:1:\"a\";i:191;s:1:\"b\";s:30:\"Payment Management Permissions\";s:1:\"c\";s:14:\"Payment Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:191;a:5:{s:1:\"a\";i:192;s:1:\"b\";s:36:\"Recovery Case Management Permissions\";s:1:\"c\";s:19:\"Recovery Case Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:192;a:5:{s:1:\"a\";i:193;s:1:\"b\";s:36:\"Recovery Case Management Permissions\";s:1:\"c\";s:20:\"Recovery Case Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:193;a:5:{s:1:\"a\";i:194;s:1:\"b\";s:36:\"Recovery Case Management Permissions\";s:1:\"c\";s:20:\"Recovery Case Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:194;a:5:{s:1:\"a\";i:195;s:1:\"b\";s:36:\"Recovery Case Management Permissions\";s:1:\"c\";s:20:\"Recovery Case Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:195;a:5:{s:1:\"a\";i:196;s:1:\"b\";s:40:\"Recovery Activity Management Permissions\";s:1:\"c\";s:23:\"Recovery Activity Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:196;a:5:{s:1:\"a\";i:197;s:1:\"b\";s:40:\"Recovery Activity Management Permissions\";s:1:\"c\";s:24:\"Recovery Activity Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:197;a:5:{s:1:\"a\";i:198;s:1:\"b\";s:40:\"Recovery Activity Management Permissions\";s:1:\"c\";s:24:\"Recovery Activity Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:198;a:5:{s:1:\"a\";i:199;s:1:\"b\";s:40:\"Recovery Activity Management Permissions\";s:1:\"c\";s:24:\"Recovery Activity Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:199;a:5:{s:1:\"a\";i:200;s:1:\"b\";s:37:\"Recovery Agent Management Permissions\";s:1:\"c\";s:20:\"Recovery Agent Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:200;a:5:{s:1:\"a\";i:201;s:1:\"b\";s:37:\"Recovery Agent Management Permissions\";s:1:\"c\";s:21:\"Recovery Agent Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:201;a:5:{s:1:\"a\";i:202;s:1:\"b\";s:37:\"Recovery Agent Management Permissions\";s:1:\"c\";s:21:\"Recovery Agent Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:202;a:5:{s:1:\"a\";i:203;s:1:\"b\";s:37:\"Recovery Agent Management Permissions\";s:1:\"c\";s:21:\"Recovery Agent Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:203;a:5:{s:1:\"a\";i:204;s:1:\"b\";s:46:\"External Recovery Agent Management Permissions\";s:1:\"c\";s:29:\"External Recovery Agent Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:204;a:5:{s:1:\"a\";i:205;s:1:\"b\";s:46:\"External Recovery Agent Management Permissions\";s:1:\"c\";s:30:\"External Recovery Agent Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:205;a:5:{s:1:\"a\";i:206;s:1:\"b\";s:46:\"External Recovery Agent Management Permissions\";s:1:\"c\";s:30:\"External Recovery Agent Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:206;a:5:{s:1:\"a\";i:207;s:1:\"b\";s:46:\"External Recovery Agent Management Permissions\";s:1:\"c\";s:30:\"External Recovery Agent Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:207;a:5:{s:1:\"a\";i:208;s:1:\"b\";s:35:\"Notification Management Permissions\";s:1:\"c\";s:18:\"Notification Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:208;a:5:{s:1:\"a\";i:209;s:1:\"b\";s:27:\"System Settings Permissions\";s:1:\"c\";s:13:\"Setting Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:209;a:5:{s:1:\"a\";i:210;s:1:\"b\";s:27:\"System Settings Permissions\";s:1:\"c\";s:14:\"Setting Update\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:210;a:5:{s:1:\"a\";i:211;s:1:\"b\";s:35:\"Credit Score Management Permissions\";s:1:\"c\";s:18:\"Credit Score Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:211;a:5:{s:1:\"a\";i:212;s:1:\"b\";s:35:\"Credit Score Management Permissions\";s:1:\"c\";s:22:\"Credit Score Recompute\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:212;a:5:{s:1:\"a\";i:213;s:1:\"b\";s:38:\"Admin Dashboard Management Permissions\";s:1:\"c\";s:21:\"Admin Dashboard Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:213;a:5:{s:1:\"a\";i:214;s:1:\"b\";s:29:\"Report Management Permissions\";s:1:\"c\";s:12:\"Report Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:214;a:5:{s:1:\"a\";i:215;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:19:\"Legal Document Sign\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:215;a:5:{s:1:\"a\";i:216;s:1:\"b\";s:28:\"Legal Management Permissions\";s:1:\"c\";s:31:\"Legal Document Clear Signatures\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:216;a:5:{s:1:\"a\";i:220;s:1:\"b\";s:25:\"Loan Security Permissions\";s:1:\"c\";s:10:\"use_plan_1\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:217;a:5:{s:1:\"a\";i:221;s:1:\"b\";s:25:\"Loan Security Permissions\";s:1:\"c\";s:10:\"use_plan_2\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:218;a:5:{s:1:\"a\";i:222;s:1:\"b\";s:25:\"Loan Security Permissions\";s:1:\"c\";s:10:\"use_plan_3\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:219;a:5:{s:1:\"a\";i:223;s:1:\"b\";s:32:\"Signature Management Permissions\";s:1:\"c\";s:15:\"Signature Index\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:220;a:5:{s:1:\"a\";i:224;s:1:\"b\";s:32:\"Signature Management Permissions\";s:1:\"c\";s:16:\"Signature Create\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:221;a:5:{s:1:\"a\";i:225;s:1:\"b\";s:32:\"Signature Management Permissions\";s:1:\"c\";s:16:\"Signature Delete\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:222;a:5:{s:1:\"a\";i:226;s:1:\"b\";s:25:\"Loan Security Permissions\";s:1:\"c\";s:15:\"use_cdp_inv_001\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:223;a:5:{s:1:\"a\";i:227;s:1:\"b\";s:25:\"Loan Security Permissions\";s:1:\"c\";s:15:\"use_cdp_pro_001\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}i:224;a:5:{s:1:\"a\";i:228;s:1:\"b\";s:25:\"Loan Security Permissions\";s:1:\"c\";s:15:\"use_cdp_vec_001\";s:1:\"d\";s:3:\"api\";s:1:\"r\";a:1:{i:0;i:1;}}}s:5:\"roles\";a:3:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"c\";s:11:\"Super Admin\";s:1:\"d\";s:3:\"api\";s:1:\"j\";i:0;}i:1;a:4:{s:1:\"a\";i:3;s:1:\"c\";s:5:\"Staff\";s:1:\"d\";s:3:\"api\";s:1:\"j\";i:0;}i:2;a:4:{s:1:\"a\";i:2;s:1:\"c\";s:8:\"Employee\";s:1:\"d\";s:3:\"api\";s:1:\"j\";i:0;}}}',1791542491),('cdp-credix-cache-TDKAnY7CkkiC5SK6','a:1:{s:11:\"valid_until\";i:1790574034;}',1793166094),('cdp-credix-cache-V4S8ygRdzNoeADMG','a:1:{s:11:\"valid_until\";i:1790317831;}',1792909831);
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
INSERT INTO `countries` VALUES (1,'Sri Lanka','LK','Sri Lanka',1,'2026-09-25 06:28:50','2026-09-25 06:28:50');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_bank_details`
--

LOCK TABLES `customer_bank_details` WRITE;
/*!40000 ALTER TABLE `customer_bank_details` DISABLE KEYS */;
INSERT INTO `customer_bank_details` VALUES (1,1,'HNB','Colombo','1234567890','Bank Transfer',1,NULL,'2026-09-25 06:34:51','2026-09-25 06:34:51'),(2,3,'NDB','Colombo','22334455','Bank Transfer',1,NULL,'2026-09-28 04:53:48','2026-09-28 04:53:48');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_details`
--

LOCK TABLES `customer_details` WRITE;
/*!40000 ALTER TABLE `customer_details` DISABLE KEYS */;
INSERT INTO `customer_details` VALUES (1,1,'Colombo','Colombo','Colombo','Western','2026-09-25 06:34:51','2026-09-25 06:34:51'),(2,2,'Colombo','Colombo','Colombo','Western','2026-09-25 07:00:47','2026-09-25 07:00:47'),(3,3,'Colombo','Colombo','Colombo','Eastern','2026-09-28 04:53:48','2026-09-28 04:53:48');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_verifications`
--

LOCK TABLES `customer_verifications` WRITE;
/*!40000 ALTER TABLE `customer_verifications` DISABLE KEYS */;
INSERT INTO `customer_verifications` VALUES (1,1,1,'0752932640',1,'{\"customer\": {\"id\": 1, \"email\": \"sulanihost@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Sulani Pabodha\", \"id_number\": \"199923456789\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0001\", \"bank_details\": [{\"bank_name\": \"HNB\", \"branch_name\": \"Colombo\", \"account_name\": null, \"account_number\": \"1234567890\"}], \"customer_code\": \"CUS0001\", \"date_of_birth\": \"2026-09-11\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.Sulani\", \"preferred_language\": \"Sinhala\"}, \"captured_at\": \"2026-10-08T13:42:06+05:30\"}','12fc351f8b0e70132c1fca72be2138df5465fb20955cdfa4542012b29499c491','superseded',0,'2026-10-08 14:12:06',NULL,NULL,'2026-10-08 08:12:06','2026-10-08 08:13:35'),(2,1,1,'0752932640',2,'{\"customer\": {\"id\": 1, \"email\": \"sulanihost@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Sulani Pabodha\", \"id_number\": \"199923456789\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0001\", \"bank_details\": [{\"bank_name\": \"HNB\", \"branch_name\": \"Colombo\", \"account_name\": null, \"account_number\": \"1234567890\"}], \"customer_code\": \"CUS0001\", \"date_of_birth\": \"2026-09-11\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.Sulani\", \"preferred_language\": \"Sinhala\"}, \"captured_at\": \"2026-10-08T13:43:35+05:30\"}','9a7754d1866d7b13f3eb1bd8758145c037326799890ed98ae1119ec2cf248c86','verified',0,'2026-10-08 14:13:35','2026-10-08 13:47:43',1,'2026-10-08 08:13:35','2026-10-08 08:17:43'),(3,1,1,'0752932640',3,'{\"customer\": {\"id\": 1, \"email\": \"sulanihost@gmail.com\", \"address\": {\"city\": \"Colombo\", \"state\": null, \"line_1\": \"12, Main rd\", \"line_2\": null, \"country\": \"Sri Lanka\", \"landmark\": null, \"postal_code\": null}, \"id_type\": \"NIC\", \"business\": {\"name\": null, \"email\": null, \"phone\": null, \"nature\": null, \"reg_no\": null}, \"full_name\": \"Sulani Pabodha\", \"id_number\": \"199923456789\", \"employment\": {\"status\": \"Employed\", \"occupation\": \"SE\", \"employer_name\": \"CDP\", \"employer_email\": null, \"employer_phone\": null, \"monthly_income\": null}, \"financials\": {\"other_income\": null, \"other_expenses\": null, \"fixed_allowances\": null, \"other_allowances\": null, \"total_monthly_income\": null, \"total_monthly_expenses\": null}, \"customer_id\": \"CUST-0001\", \"bank_details\": [{\"bank_name\": \"HNB\", \"branch_name\": \"Colombo\", \"account_name\": null, \"account_number\": \"1234567890\"}], \"customer_code\": \"CUS0001\", \"date_of_birth\": \"2026-09-11\", \"have_whatsapp\": false, \"phone_primary\": \"0752932640\", \"region_details\": {\"district\": \"Colombo\", \"province\": \"Western\", \"ds_division\": \"Colombo\", \"gn_division\": \"Colombo\"}, \"phone_secondary\": null, \"whatsapp_number\": null, \"name_with_initials\": \"P.Sulani\", \"preferred_language\": \"Sinhala\"}, \"captured_at\": \"2026-10-08T13:50:36+05:30\"}','1d18235cac9d50ae9be4209de5eaad9036f4fc2bf11efe482b35d290c1047567','pending',0,'2026-10-08 14:20:36',NULL,NULL,'2026-10-08 08:20:36','2026-10-08 08:20:36');
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
) ENGINE=InnoDB AUTO_INCREMENT=214 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'CUST-0001','CUS0001','Sulani Pabodha','P.Sulani','NIC','199923456789','2026-09-11','12, Main rd',NULL,NULL,'Colombo',NULL,'Sri Lanka',NULL,'0752932640',NULL,'sulanihost@gmail.com',0,NULL,'Sinhala','Employed','SE','CDP',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2026-10-08 13:47:43',2,NULL,'2026-09-25 06:34:51','2026-10-08 08:17:43'),(2,'CUST-0002','CUS0002','Piranya Paskaran','P.Piranya','NIC','199812345678','2026-09-03','12, Main rd',NULL,NULL,'Colombo',NULL,'Sri Lanka',NULL,'0752932640',NULL,'piranya@gmail.com',0,NULL,'Sinhala','Employed','SE','CDP',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,'2026-09-25 07:00:46','2026-09-25 07:00:46'),(3,'CUST-0003','CUS0003','Thipanujan Raja','R.Thipanu','NIC','200612345678','2026-09-03','12, Main rd',NULL,NULL,'Colombo',NULL,'Sri Lanka',NULL,'0752932640',NULL,'vedhasiricdp@gmail.com',0,NULL,'Tamil','Employed','Teacher','School',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,'2026-09-28 04:53:48','2026-09-28 04:53:48');
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
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES (1,'nic_copy',0,'WhatsApp Image 2026-09-18 at 4.11.59 PM','uploads/documents/1_WhatsApp_Image_2026-09-18_at_4_11_59_PM_ayNokXCb.jpeg',1,NULL,NULL,NULL,'2026-09-25 06:34:52','active',NULL,1,NULL,'2026-09-25 06:34:52','2026-09-25 06:34:52'),(2,'passport_copy',0,'WhatsApp Image 2026-09-18 at 4.11.59 PM','uploads/documents/2_WhatsApp_Image_2026-09-18_at_4_11_59_PM_RlG8Oubs.jpeg',2,NULL,NULL,NULL,'2026-09-25 07:00:47','active',NULL,1,NULL,'2026-09-25 07:00:47','2026-09-25 07:00:47'),(3,'nic_copy',0,'WhatsApp Image 2026-09-18 at 4.11.59 PM','uploads/documents/WhatsApp_Image_2026-09-18_at_4_11_59_PM_bVxCeUQC.jpeg',2,1,1,1,'2026-09-25 09:42:26','active',NULL,1,NULL,'2026-09-25 09:42:26','2026-09-25 09:42:26'),(4,'nic_copy',0,'WhatsApp Image 2026-09-18 at 4.11.59 PM','uploads/documents/WhatsApp_Image_2026-09-18_at_4_11_59_PM_TcK4mCJo.jpeg',2,1,2,1,'2026-09-25 09:42:29','active',NULL,1,NULL,'2026-09-25 09:42:29','2026-09-25 09:42:29'),(5,'billing_proof',0,'Billing Proof','uploads/documents/Billing_Proof_OEkgU3V5.pdf',2,1,NULL,1,'2026-09-25 09:57:04','active','Application: APP-BRMAIN001-2609250001 | Customer: Piranya Paskaran',1,NULL,'2026-09-25 09:57:04','2026-09-25 09:57:04'),(6,'salary_slip',0,'Salary Slips (last 3 months)','uploads/documents/Salary_Slips_last_3_months_CgsamkNc.pdf',2,1,NULL,1,'2026-09-25 09:57:27','active','Application: APP-BRMAIN001-2609250001 | Customer: Piranya Paskaran',1,NULL,'2026-09-25 09:57:27','2026-09-25 09:57:27'),(7,'salary_assignment_letter',0,'Salary Confirmation Letter','uploads/documents/Salary_Confirmation_Letter_GwhRbxab.pdf',2,1,NULL,1,'2026-09-25 09:57:35','active','Application: APP-BRMAIN001-2609250001 | Customer: Piranya Paskaran',1,NULL,'2026-09-25 09:57:35','2026-09-25 09:57:35'),(8,'nic_copy',0,'WhatsApp Image 2026-09-18 at 4.11.59 PM','uploads/documents/3_WhatsApp_Image_2026-09-18_at_4_11_59_PM_dnMVaVoh.jpeg',3,NULL,NULL,NULL,'2026-09-28 04:53:49','active',NULL,1,NULL,'2026-09-28 04:53:49','2026-09-28 04:53:49'),(75,'nic_copy',0,'NIC Copy','uploads/documents/NIC_Copy_R6mdaOZJ.jpeg',2,1,NULL,1,'2026-10-07 06:59:49','active','Application: APP-BRMAIN001-2609250001 | Customer: Piranya Paskaran',1,NULL,'2026-10-07 06:59:49','2026-10-07 06:59:49'),(76,'driving_license',0,'Driving License','uploads/documents/Driving License.pdf',2,1,NULL,1,'2026-10-08 09:03:22','active','Application: APP-BRMAIN001-2609250001 | Customer: Piranya Paskaran',1,'2026-10-08 09:11:03','2026-10-08 09:03:22','2026-10-08 09:11:03'),(77,'employer_letter',0,'Employment Confirmation Letter','uploads/documents/Employment Confirmation Letter.pdf',NULL,1,2,1,'2026-10-08 09:11:58','active','Application: APP-BRMAIN001-2609250001 | Guarantor: Inshath',1,NULL,'2026-10-08 09:11:58','2026-10-08 09:11:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'Pathma','','Pathma','Pathma','EMP-NUG-001',NULL,1,1,1,2,NULL,NULL,NULL,NULL,'1998214354678',NULL,'sulani98@gmail.com',NULL,NULL,NULL,NULL,'Sri Lanka',NULL,NULL,NULL,0,NULL,NULL,NULL,1,'2026-09-25 07:54:47','2026-09-25 07:54:47');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `external_recovery_agents`
--

LOCK TABLES `external_recovery_agents` WRITE;
/*!40000 ALTER TABLE `external_recovery_agents` DISABLE KEYS */;
INSERT INTO `external_recovery_agents` VALUES (1,'Northern Debt Recovery Services','0212223344','ops@northernrecovery.lk','No. 12, Hospital Road, Jaffna',1,'Handles Jaffna and Kilinochchi districts','2026-09-25 07:16:00','2026-09-25 07:16:00'),(2,'Lanka Credit Collections (Pvt) Ltd','0112345678','collections@lankacredit.lk','45, Galle Road, Colombo 03',1,'Western province, legal follow-up available','2026-09-25 07:16:00','2026-09-25 07:16:00'),(3,'Mannar Recovery Associates','0232250101','info@mannarrecovery.lk','Main Street, Mannar',1,NULL,'2026-09-25 07:16:00','2026-09-25 07:16:00'),(4,'Vanni Field Collectors','0242222555',NULL,'Kandy Road, Vavuniya',0,'Field visits only','2026-09-25 07:16:00','2026-09-25 07:16:00');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fixed_assests`
--

LOCK TABLES `fixed_assests` WRITE;
/*!40000 ALTER TABLE `fixed_assests` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guarantors`
--

LOCK TABLES `guarantors` WRITE;
/*!40000 ALTER TABLE `guarantors` DISABLE KEYS */;
INSERT INTO `guarantors` VALUES (1,2,'Rifkey','guarantor_1','NIC','200012345678',NULL,NULL,'0752932640','Employed','SE','CDP',NULL,NULL,NULL,NULL,125000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-25 09:42:25','2026-09-25 09:42:25'),(2,2,'Inshath','guarantor_2','NIC','200087654321',NULL,NULL,'0752932640','Employed','SE','CDP',NULL,NULL,NULL,NULL,125000.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-25 09:42:27','2026-09-25 09:42:27');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES (1,'default','{\"uuid\":\"f0010566-b741-4e49-89d9-2496280ef33b\",\"displayName\":\"App\\\\Jobs\\\\SendSmsJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSmsJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSmsJob\\\":3:{s:7:\\\"numbers\\\";s:10:\\\"0752932640\\\";s:7:\\\"message\\\";s:72:\\\"CDP Credix: Your OTP for password reset is 717884. Valid for 60 minutes.\\\";s:14:\\\"notificationId\\\";N;}\"},\"createdAt\":1790574215,\"delay\":null}',0,NULL,1790574215,1790574215);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legal_document_signatures`
--

DROP TABLE IF EXISTS `legal_document_signatures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_document_signatures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `legal_document_id` bigint unsigned NOT NULL,
  `signer_key` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signer_role` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `guarantor_id` bigint unsigned DEFAULT NULL,
  `signer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signer_nic` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signer_designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature_mime` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature_sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `captured_by` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `voided_by` bigint unsigned DEFAULT NULL,
  `void_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `legal_document_signatures_customer_id_foreign` (`customer_id`),
  KEY `legal_document_signatures_guarantor_id_foreign` (`guarantor_id`),
  KEY `legal_document_signatures_captured_by_foreign` (`captured_by`),
  KEY `legal_document_signatures_voided_by_foreign` (`voided_by`),
  KEY `legal_signatures_document_line_index` (`legal_document_id`,`signer_key`,`voided_at`),
  CONSTRAINT `legal_document_signatures_captured_by_foreign` FOREIGN KEY (`captured_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `legal_document_signatures_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `legal_document_signatures_guarantor_id_foreign` FOREIGN KEY (`guarantor_id`) REFERENCES `guarantors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `legal_document_signatures_legal_document_id_foreign` FOREIGN KEY (`legal_document_id`) REFERENCES `legal_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `legal_document_signatures_voided_by_foreign` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_document_signatures`
--

LOCK TABLES `legal_document_signatures` WRITE;
/*!40000 ALTER TABLE `legal_document_signatures` DISABLE KEYS */;
/*!40000 ALTER TABLE `legal_document_signatures` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `signed_at` timestamp NULL DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_documents`
--

LOCK TABLES `legal_documents` WRITE;
/*!40000 ALTER TABLE `legal_documents` DISABLE KEYS */;
INSERT INTO `legal_documents` VALUES (1,1,NULL,NULL,NULL,1,NULL,'LEG-000001','direct_loan_agreement','en','created','2026-10-02 08:11:26',NULL,'{\"witness1Nic\": null, \"witness2Nic\": null, \"handoverDate\": null, \"witness1Name\": null, \"witness2Name\": null, \"agreementDate\": null, \"witness1Address\": null, \"witness2Address\": null, \"investmentAmount\": 250000, \"agreementLocation\": null, \"guarantor1Address\": null, \"guarantor2Address\": null, \"otherSurrenderedDocs\": null, \"surrenderedDocCertNo\": null, \"authorizedOfficerName\": null, \"customerNameLocalized\": null, \"investmentAgreementNo\": null, \"receivedByOfficerName\": null, \"surrenderedDocAgrmtNo\": null, \"witness1NameLocalized\": null, \"witness2NameLocalized\": null, \"investmentMaturityDate\": null, \"guarantor1NameLocalized\": null, \"guarantor2NameLocalized\": null, \"investmentAgreementDate\": null, \"investmentCertificateNo\": null, \"customerAddressLocalized\": null, \"witness1AddressLocalized\": null, \"witness2AddressLocalized\": null, \"investmentCertificateDate\": null, \"agreementLocationLocalized\": null, \"guarantor1AddressLocalized\": null, \"guarantor2AddressLocalized\": null, \"authorizedOfficerDesignation\": null, \"receivedByOfficerDesignation\": null, \"authorizedOfficerNameLocalized\": null, \"authorizedOfficerDesignationLocalized\": null}',0,NULL,NULL,1,NULL,'2026-10-02 08:11:26','2026-10-02 08:11:26');
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
INSERT INTO `lending_mortgages` VALUES (1,'CDP investment','Investment Plan 1','CDP-INV-001',50.00,'active','2026-10-05 15:17:28','2026-10-05 15:17:28'),(2,'CDP investment','Investment Plan 2','CDP-INV-002',60.00,'active','2026-10-05 15:17:28','2026-10-05 15:17:28'),(3,'CDP investment','Investment Plan 3','CDP-INV-003',70.00,'active','2026-10-05 15:17:28','2026-10-05 15:17:28'),(4,'Property','Property Mortgage Plan 1','CDP-PRO-001',70.00,'active','2026-10-05 15:17:28','2026-10-05 15:17:28'),(5,'Property','Property Mortgage Plan 2','CDP-PRO-002',50.00,'active','2026-10-05 15:17:28','2026-10-05 15:17:28'),(6,'Property','Property Mortgage Plan 3','CDP-PRO-003',60.00,'active','2026-10-05 15:17:28','2026-10-05 15:17:28'),(7,'Vehicle','Vehicle Plan 1','CDP-VEC-001',70.00,'active','2026-10-05 15:17:28','2026-10-05 15:17:28'),(8,'Vehicle','Vehicle Plan 2','CDP-VEC-002',50.00,'active','2026-10-05 15:17:28','2026-10-05 15:17:28'),(9,'Vehicle','Vehicle Plan 3','CDP-VEC-003',60.00,'active','2026-10-05 15:17:28','2026-10-05 15:17:28');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `liabilities`
--

LOCK TABLES `liabilities` WRITE;
/*!40000 ALTER TABLE `liabilities` DISABLE KEYS */;
/*!40000 ALTER TABLE `liabilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_application_bank_details`
--

DROP TABLE IF EXISTS `loan_application_bank_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_application_bank_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned NOT NULL,
  `customer_bank_detail_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_app_bank_detail_unique` (`loan_application_id`,`customer_bank_detail_id`),
  KEY `loan_application_bank_details_customer_bank_detail_id_foreign` (`customer_bank_detail_id`),
  CONSTRAINT `loan_application_bank_details_customer_bank_detail_id_foreign` FOREIGN KEY (`customer_bank_detail_id`) REFERENCES `customer_bank_details` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_application_bank_details_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_application_bank_details`
--

LOCK TABLES `loan_application_bank_details` WRITE;
/*!40000 ALTER TABLE `loan_application_bank_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_application_bank_details` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_application_customers`
--

LOCK TABLES `loan_application_customers` WRITE;
/*!40000 ALTER TABLE `loan_application_customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_application_customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_application_fixed_assets`
--

DROP TABLE IF EXISTS `loan_application_fixed_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_application_fixed_assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned NOT NULL,
  `fixed_assest_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_app_fixed_asset_unique` (`loan_application_id`,`fixed_assest_id`),
  KEY `loan_application_fixed_assets_fixed_assest_id_foreign` (`fixed_assest_id`),
  CONSTRAINT `loan_application_fixed_assets_fixed_assest_id_foreign` FOREIGN KEY (`fixed_assest_id`) REFERENCES `fixed_assests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_application_fixed_assets_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_application_fixed_assets`
--

LOCK TABLES `loan_application_fixed_assets` WRITE;
/*!40000 ALTER TABLE `loan_application_fixed_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_application_fixed_assets` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_application_guarantors`
--

LOCK TABLES `loan_application_guarantors` WRITE;
/*!40000 ALTER TABLE `loan_application_guarantors` DISABLE KEYS */;
INSERT INTO `loan_application_guarantors` VALUES (1,1,1,'guarantor_1','pending',NULL,'2026-09-25 09:42:26','2026-09-25 09:42:26'),(2,1,2,'guarantor_2','pending',NULL,'2026-09-25 09:42:28','2026-09-25 09:42:28');
/*!40000 ALTER TABLE `loan_application_guarantors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_application_liabilities`
--

DROP TABLE IF EXISTS `loan_application_liabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_application_liabilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned NOT NULL,
  `liability_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_app_liability_unique` (`loan_application_id`,`liability_id`),
  KEY `loan_application_liabilities_liability_id_foreign` (`liability_id`),
  CONSTRAINT `loan_application_liabilities_liability_id_foreign` FOREIGN KEY (`liability_id`) REFERENCES `liabilities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_application_liabilities_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_application_liabilities`
--

LOCK TABLES `loan_application_liabilities` WRITE;
/*!40000 ALTER TABLE `loan_application_liabilities` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_application_liabilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_application_moving_assets`
--

DROP TABLE IF EXISTS `loan_application_moving_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_application_moving_assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_application_id` bigint unsigned NOT NULL,
  `moving_assest_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_app_moving_asset_unique` (`loan_application_id`,`moving_assest_id`),
  KEY `loan_application_moving_assets_moving_assest_id_foreign` (`moving_assest_id`),
  CONSTRAINT `loan_application_moving_assets_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_application_moving_assets_moving_assest_id_foreign` FOREIGN KEY (`moving_assest_id`) REFERENCES `moving_assests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_application_moving_assets`
--

LOCK TABLES `loan_application_moving_assets` WRITE;
/*!40000 ALTER TABLE `loan_application_moving_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_application_moving_assets` ENABLE KEYS */;
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
  KEY `loan_application_securities_security_type_index` (`security_type`),
  KEY `loan_application_securities_policy_number_index` (`policy_number`),
  KEY `loan_application_securities_registration_number_index` (`registration_number`),
  KEY `loan_application_securities_application_index` (`loan_application_id`),
  CONSTRAINT `loan_application_securities_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_application_securities`
--

LOCK TABLES `loan_application_securities` WRITE;
/*!40000 ALTER TABLE `loan_application_securities` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=310 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_application_status_history`
--

LOCK TABLES `loan_application_status_history` WRITE;
/*!40000 ALTER TABLE `loan_application_status_history` DISABLE KEYS */;
INSERT INTO `loan_application_status_history` VALUES (1,1,1,'submitted',1,'Loan application submitted','2026-09-25','2026-09-25 09:42:24',NULL,'2026-09-25 09:42:24','2026-09-25 09:42:24'),(2,1,1,'review_failed',1,'you need to submit further documents','2026-09-25','2026-09-25 09:53:14',NULL,'2026-09-25 09:53:14','2026-09-25 09:53:14'),(309,1,1,'submitted',1,'resubmit','2026-10-08','2026-10-08 05:48:41',NULL,'2026-10-08 05:48:41','2026-10-08 05:48:41');
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
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_applications`
--

LOCK TABLES `loan_applications` WRITE;
/*!40000 ALTER TABLE `loan_applications` DISABLE KEYS */;
INSERT INTO `loan_applications` VALUES (1,1,2,1,1,NULL,500000.00,NULL,10.000,'flat',12,NULL,1500.00,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-08 05:48:41',1,NULL,NULL,0,'2026-09-25 09:42:24',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'submitted',NULL,1,NULL,'2026-09-25 09:42:24','2026-10-08 05:48:41');
/*!40000 ALTER TABLE `loan_applications` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_documents`
--

LOCK TABLES `loan_documents` WRITE;
/*!40000 ALTER TABLE `loan_documents` DISABLE KEYS */;
INSERT INTO `loan_documents` VALUES (1,1,2,NULL,NULL,NULL,NULL,'2026-09-25 09:42:24','2026-09-25 09:42:24'),(2,1,3,NULL,NULL,NULL,NULL,'2026-09-25 09:42:26','2026-09-25 09:42:26'),(3,1,4,NULL,NULL,NULL,NULL,'2026-09-25 09:42:29','2026-09-25 09:42:29'),(4,1,5,NULL,NULL,NULL,NULL,'2026-09-25 09:57:04','2026-09-25 09:57:04'),(5,1,6,NULL,NULL,NULL,NULL,'2026-09-25 09:57:27','2026-09-25 09:57:27'),(6,1,7,NULL,NULL,NULL,NULL,'2026-09-25 09:57:35','2026-09-25 09:57:35'),(73,1,75,NULL,NULL,NULL,NULL,'2026-10-07 06:59:49','2026-10-07 06:59:49'),(74,1,76,NULL,NULL,NULL,NULL,'2026-10-08 09:03:22','2026-10-08 09:03:22'),(75,1,77,NULL,NULL,NULL,NULL,'2026-10-08 09:11:58','2026-10-08 09:11:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_installments`
--

LOCK TABLES `loan_installments` WRITE;
/*!40000 ALTER TABLE `loan_installments` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_products`
--

LOCK TABLES `loan_products` WRITE;
/*!40000 ALTER TABLE `loan_products` DISABLE KEYS */;
INSERT INTO `loan_products` VALUES (1,'Personal Loan','PLN-001',1,1,'Personal loan for individual borrowers.',10.000,'flat',100000.00,50000000.00,6,60,'fixed',1500.00,NULL,0,1,0,0,NULL,'2026-09-25 06:27:42','2026-09-25 06:27:42'),(2,'Business Loan','PLN-002',1,1,NULL,10.000,'flat',1000000.00,50000000.00,6,60,'fixed',100000.00,1000.00,0,1,0,0,NULL,'2026-09-25 06:39:02','2026-09-25 06:40:47'),(3,'Mortgage Loan','PLN-003',1,1,NULL,10.000,'flat',10000.00,500000.00,6,60,'fixed',1000.00,1000.00,0,1,0,0,'2026-10-02 04:59:14','2026-09-25 06:40:13','2026-10-02 04:59:14');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_term_loan_type`
--

LOCK TABLES `loan_term_loan_type` WRITE;
/*!40000 ALTER TABLE `loan_term_loan_type` DISABLE KEYS */;
INSERT INTO `loan_term_loan_type` VALUES (1,1,1,NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_terms`
--

LOCK TABLES `loan_terms` WRITE;
/*!40000 ALTER TABLE `loan_terms` DISABLE KEYS */;
INSERT INTO `loan_terms` VALUES (1,'GENERAL','General','General purpose loan tenure.',1,'2026-09-25 06:27:42','2026-09-25 06:27:42'),(2,'ISLAMIC','Islamic','Islamic Loans',1,'2026-09-25 06:36:18','2026-09-25 06:36:40');
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
INSERT INTO `loan_types` VALUES (1,'STANDARD_BORROWING',1,'Standard Borrowing','Standard borrowing facility.',1,'2026-09-25 06:27:42','2026-09-25 06:27:42'),(2,'DEVELOPMENT_FUND',1,'Development Fund',NULL,1,'2026-09-25 06:37:38','2026-09-25 06:37:38');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_otp_verifications`
--

LOCK TABLES `login_otp_verifications` WRITE;
/*!40000 ALTER TABLE `login_otp_verifications` DISABLE KEYS */;
INSERT INTO `login_otp_verifications` VALUES (1,9,'4bfae60e-bfd8-4cc4-b54d-0562f9491874','875463','2026-09-28 04:55:50','verified','2026-09-28 04:55:19','127.0.0.1','2026-09-28 04:54:50','2026-09-28 04:55:19');
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
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2025_10_07_055655_create_personal_access_tokens_table',1),(4,'2025_11_23_175058_create_permission_tables',1),(5,'2026_06_15_114550_create_countries_table',1),(6,'2026_06_15_114551_create_groups_table',1),(7,'2026_06_15_114552_create_provinces_table',1),(8,'2026_06_15_114553_create_zonals_table',1),(9,'2026_06_15_114554_create_regions_table',1),(10,'2026_06_15_114555_create_branches_table',1),(11,'2026_06_15_114556_create_departments_table',1),(12,'2026_06_15_114557_create_designations_table',1),(13,'2026_06_15_114558_create_applications_table',1),(14,'2026_06_15_114558_create_customers_table',1),(15,'2026_06_15_114558_create_employees_table',1),(16,'2026_06_15_114559_create_users_table',1),(17,'2026_06_15_114600_add_customer_recommender_foreign_key',1),(18,'2026_06_15_120000_create_activity_logs_table',1),(19,'2026_07_15_120300_create_application_history_table',1),(20,'2026_07_15_120400_create_customer_bank_details_table',1),(21,'2026_07_15_120500_create_guarantors_table',1),(22,'2026_07_15_120600_create_fixed_assests_table',1),(23,'2026_07_15_120700_create_moving_assests_table',1),(24,'2026_07_16_152000_create_documents_table',1),(25,'2026_07_20_065100_create_loan_types_table',1),(26,'2026_07_20_065200_create_loan_terms_table',1),(27,'2026_07_20_065300_create_loan_term_loan_type_table',1),(28,'2026_07_20_070000_create_loan_products_table',1),(29,'2026_07_20_075000_create_group_loans_table',1),(30,'2026_07_20_075100_create_group_loan_items_table',1),(31,'2026_07_20_080000_create_loan_applications_table',1),(32,'2026_07_20_080100_add_document_loan_application_foreign_key',1),(33,'2026_07_20_090000_create_loan_application_guarantors_table',1),(34,'2026_08_03_100100_create_loan_application_status_history_table',1),(35,'2026_08_03_105000_create_loan_revisions_table',1),(36,'2026_08_03_110000_create_loan_installments_table',1),(37,'2026_08_03_120000_create_payments_table',1),(38,'2026_08_03_130000_create_external_recovery_agents_table',1),(39,'2026_08_03_140000_create_recovery_cases_table',1),(40,'2026_08_03_150000_create_recovery_activities_table',1),(41,'2026_08_03_160000_create_recovery_agents_table',1),(42,'2026_08_03_180000_create_notifications_table',1),(43,'2026_08_07_120100_create_password_change_requests_table',1),(44,'2026_08_12_100100_create_login_otp_verifications_table',1),(45,'2026_08_12_100200_create_liabilities_table',1),(46,'2026_08_13_090000_create_settings_table',1),(47,'2026_08_13_110200_create_loan_application_fixed_assets_table',1),(48,'2026_08_13_110300_create_loan_application_moving_assets_table',1),(49,'2026_08_13_110400_create_loan_application_liabilities_table',1),(50,'2026_08_13_110500_create_loan_application_bank_details_table',1),(51,'2026_08_21_090000_create_admin_dashboard_table',1),(52,'2026_09_02_090100_create_customer_details_table',1),(53,'2026_09_02_090200_create_loan_application_customers_table',1),(54,'2026_09_02_100000_add_loan_term_id_to_loan_types_table',1),(55,'2026_09_03_100000_set_islamic_false_default_on_loan_products',1),(56,'2026_09_09_100000_create_credit_score_events_table',1),(57,'2026_09_09_100100_create_customer_credit_scores_table',1),(58,'2026_09_11_140000_create_legal_document_templates_table',1),(59,'2026_09_11_140100_create_legal_documents_table',1),(60,'2026_09_16_000000_add_member_snapshot_to_loan_application_customers_table',1),(61,'2026_09_16_100000_change_group_loan_items_quantity_to_integer',1),(62,'2026_09_21_120000_create_loan_documents_table',1),(63,'2026_09_28_100000_create_loan_application_securities_table',2),(68,'2026_10_02_100000_create_legal_document_signatures_table',3),(69,'2026_10_02_110000_create_loan_product_legal_documents_table',3),(72,'2026_10_05_090000_allow_multiple_loan_securities_with_ltv',4),(73,'2026_10_05_091000_add_loan_security_percentage_settings',4),(76,'2026_10_05_092000_add_security_plan_to_securities',5),(77,'2026_10_05_093000_add_security_plans_and_permissions',5),(80,'2026_10_05_100000_create_lending_mortgages_table',6),(81,'2026_10_07_103419_create_signatures_table',7),(82,'2026_10_08_120000_create_customer_verifications_table',8);
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
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(2,'App\\Models\\User',5),(2,'App\\Models\\User',6),(2,'App\\Models\\User',7),(3,'App\\Models\\User',8);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `moving_assests`
--

LOCK TABLES `moving_assests` WRITE;
/*!40000 ALTER TABLE `moving_assests` DISABLE KEYS */;
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
  `channel` enum('email','sms') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,NULL,1,3,'customer_registration_credentials','email','sulanihost@gmail.com','Your CDP Capital Account Credentials','Login credentials email sent to customer.','55797ae679a6f4c0e3f562acfe2a3fd5966a6b34a34df6fb8a9f14995b510b25','sent',NULL,'2026-09-25 06:34:55','2026-09-25 06:34:52','2026-09-25 06:34:55'),(2,NULL,2,4,'customer_registration_credentials','email','piranya@gmail.com','Your CDP Capital Account Credentials','Login credentials email sent to customer.','e0f874e4a629f02e4b4ca04a6de20399e5e5ad2baf2f6de707a8f15f2e586a59','sent',NULL,'2026-09-25 07:00:50','2026-09-25 07:00:47','2026-09-25 07:00:50'),(3,1,2,NULL,'application_review_failed','sms','0752932640',NULL,'Dear Piranya Paskaran,\n\nLoan Ref: APP-BRMAIN001-2609250001\n\nYour loan application has been reviewed but it failed.\nReason: you need to submit further documents\nPlease resubmit your loan application documents again.\n\nBest Wishes,\nCDP Capital (PVT) LTD.','87265253980627226c96587102c5e9bdaa0e4a1799c712670feadd62ea50438f','sent',NULL,'2026-09-25 09:53:16','2026-09-25 09:53:14','2026-09-25 09:53:16'),(4,NULL,3,9,'customer_registration_credentials','email','vedhasiricdp@gmail.com','Your CDP Capital Account Credentials','Login credentials email sent to customer.','89c103621e53166f43d047a06ccb16b9ba59606fc13c05570d0b59a2ab374ce8','sent',NULL,'2026-09-28 04:53:54','2026-09-28 04:53:49','2026-09-28 04:53:54');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_change_requests`
--

LOCK TABLES `password_change_requests` WRITE;
/*!40000 ALTER TABLE `password_change_requests` DISABLE KEYS */;
INSERT INTO `password_change_requests` VALUES (1,9,NULL,'717884','2026-09-28 06:43:35','verified','2026-09-28 05:43:35','2026-09-28 05:53:10');
/*!40000 ALTER TABLE `password_change_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
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
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=229 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'Activity Management Permissions','Activity Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(2,'Activity Management Permissions','Activity Show','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(3,'Access Management Permissions','Permission Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(4,'Access Management Permissions','Permission Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(5,'Access Management Permissions','Permission Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(6,'Access Management Permissions','Permission Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(7,'Access Management Permissions','Role Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(8,'Access Management Permissions','Role Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(9,'Access Management Permissions','Role Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(10,'Access Management Permissions','Role Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(11,'User Management Permissions','User Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(12,'User Management Permissions','User Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(13,'User Management Permissions','User Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(14,'User Management Permissions','User Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(15,'User Management Permissions','User Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(16,'Employee Management Permissions','Employee Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(17,'Country Management Permissions','Country Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(18,'Country Management Permissions','Country Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(19,'Country Management Permissions','Country Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(20,'Country Management Permissions','Country Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(21,'Country Management Permissions','Country Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(22,'Province Management Permissions','Province Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(23,'Province Management Permissions','Province Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(24,'Province Management Permissions','Province Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(25,'Province Management Permissions','Province Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(26,'Province Management Permissions','Province Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(27,'Zonal Management Permissions','Zonal Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(28,'Zonal Management Permissions','Zonal Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(29,'Zonal Management Permissions','Zonal Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(30,'Zonal Management Permissions','Zonal Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(31,'Zonal Management Permissions','Zonal Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(32,'Region Management Permissions','Region Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(33,'Region Management Permissions','Region Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(34,'Region Management Permissions','Region Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(35,'Region Management Permissions','Region Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(36,'Region Management Permissions','Region Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(37,'Branch Management Permissions','Branch Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(38,'Branch Management Permissions','Branch Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(39,'Branch Management Permissions','Branch Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(40,'Branch Management Permissions','Branch Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(41,'Branch Management Permissions','Branch Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(42,'Department Management Permissions','Department Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(43,'Department Management Permissions','Department Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(44,'Department Management Permissions','Department Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(45,'Department Management Permissions','Department Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(46,'Department Management Permissions','Department Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(47,'Designation Management Permissions','Designation Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(48,'Designation Management Permissions','Designation Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(49,'Designation Management Permissions','Designation Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(50,'Designation Management Permissions','Designation Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(51,'Designation Management Permissions','Designation Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(52,'Group Management Permissions','Group Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(53,'Group Management Permissions','Group Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(54,'Group Management Permissions','Group Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(55,'Group Management Permissions','Group Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(56,'Group Management Permissions','Group Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(57,'Customer Management Permissions','Customer Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(58,'Customer Management Permissions','Customer Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(59,'Customer Management Permissions','Customer Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(60,'Customer Management Permissions','Customer Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(61,'Customer Management Permissions','Customer Restore','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(62,'Customer Management Permissions','Customer Force Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(63,'Customer Management Permissions','Customer Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(64,'Customer Bank Detail Management Permissions','Customer Bank Detail Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(65,'Customer Bank Detail Management Permissions','Customer Bank Detail Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(66,'Customer Bank Detail Management Permissions','Customer Bank Detail Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(67,'Customer Bank Detail Management Permissions','Customer Bank Detail Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(68,'Customer Bank Detail Management Permissions','Customer Bank Detail Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(69,'Guarantor Management Permissions','Guarantor Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(70,'Guarantor Management Permissions','Guarantor Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(71,'Guarantor Management Permissions','Guarantor Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(72,'Guarantor Management Permissions','Guarantor Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(73,'Application Management Permissions','Application Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(74,'Application Management Permissions','Application Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(75,'Application Management Permissions','Application Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(76,'Application Management Permissions','Application Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(77,'Application History Management Permissions','Application History Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(78,'Application History Management Permissions','Application History Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(79,'Application History Management Permissions','Application History Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(80,'Application History Management Permissions','Application History Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(81,'Fixed Asset Management Permissions','Fixed Asset Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(82,'Fixed Asset Management Permissions','Fixed Asset Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(83,'Fixed Asset Management Permissions','Fixed Asset Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(84,'Fixed Asset Management Permissions','Fixed Asset Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(85,'Moving Asset Management Permissions','Moving Asset Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(86,'Moving Asset Management Permissions','Moving Asset Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(87,'Moving Asset Management Permissions','Moving Asset Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(88,'Moving Asset Management Permissions','Moving Asset Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(89,'Liability Management Permissions','Liability Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(90,'Liability Management Permissions','Liability Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(91,'Liability Management Permissions','Liability Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(92,'Liability Management Permissions','Liability Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(93,'Liability Management Permissions','Liability Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(94,'Document Management Permissions','Document Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(95,'Document Management Permissions','Document Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(96,'Document Management Permissions','Document Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(97,'Document Management Permissions','Document Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(98,'Loan Term Management Permissions','Loan Term Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(99,'Loan Term Management Permissions','Loan Term Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(100,'Loan Term Management Permissions','Loan Term Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(101,'Loan Term Management Permissions','Loan Term Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(102,'Loan Term Management Permissions','Loan Term Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(103,'Loan Type Management Permissions','Loan Type Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(104,'Loan Type Management Permissions','Loan Type Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(105,'Loan Type Management Permissions','Loan Type Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(106,'Loan Type Management Permissions','Loan Type Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(107,'Loan Type Management Permissions','Loan Type Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(108,'Loan Product Management Permissions','Loan Product Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(109,'Loan Product Management Permissions','Loan Product Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(110,'Loan Product Management Permissions','Loan Product Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(111,'Loan Product Management Permissions','Loan Product Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(112,'Loan Product Management Permissions','Loan Product Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(113,'Global Search Permissions','Global Search','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(114,'CDP Connect Permissions','CDP Customer Verification','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(115,'Loan Application Management Permissions','Loan Application Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(116,'Loan Application Management Permissions','Loan Application Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(117,'Loan Application Management Permissions','Loan Application Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(118,'Loan Application Management Permissions','Loan Application Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(119,'Loan Application Management Permissions','Loan Application Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(120,'Loan Application Management Permissions','Loan Application Review','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(121,'Loan Application Management Permissions','Loan Application Resubmit','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(122,'Loan Application Management Permissions','Loan Application Verify','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(123,'Loan Application Management Permissions','Loan Application Reverify','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(124,'Loan Application Management Permissions','Loan Application Approve','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(125,'Loan Application Management Permissions','Loan Application Reject','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(126,'Loan Application Management Permissions','Loan Application Reopen','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(127,'Loan Application Management Permissions','Loan Application Offer Response','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(128,'Loan Application Management Permissions','Loan Application Status History Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(129,'Legal Management Permissions','Legal Template Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(130,'Legal Management Permissions','Legal Template Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(131,'Legal Management Permissions','Legal Template Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(132,'Legal Management Permissions','Legal Template Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(133,'Legal Management Permissions','Legal Template Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(134,'Legal Management Permissions','Legal Document Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(135,'Legal Management Permissions','Legal Document Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(136,'Legal Management Permissions','Legal Document Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(137,'Legal Management Permissions','Legal Document Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(138,'Legal Management Permissions','Legal Document Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(139,'Loan Application Management Permissions','Loan Application Disburse','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(140,'Loan Application Management Permissions','Loan Application Cancel','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(141,'Group Loan Management Permissions','Group Loan Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(142,'Group Loan Management Permissions','Group Loan Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(143,'Group Loan Management Permissions','Group Loan Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(144,'Group Loan Management Permissions','Group Loan Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(145,'Group Loan Management Permissions','Group Loan Toggle Status','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(146,'Group Loan Management Permissions','Group Loan Review','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(147,'Group Loan Management Permissions','Group Loan Resubmit','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(148,'Group Loan Management Permissions','Group Loan Verify','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(149,'Group Loan Management Permissions','Group Loan Reverify','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(150,'Group Loan Management Permissions','Group Loan Approve','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(151,'Group Loan Management Permissions','Group Loan Reject','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(152,'Group Loan Management Permissions','Group Loan Reopen','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(153,'Group Loan Management Permissions','Group Loan Offer Response','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(154,'Group Loan Management Permissions','Group Loan Disburse','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(155,'Group Loan Management Permissions','Group Loan Cancel','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(156,'Group Loan Item Management Permissions','Group Loan Item Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(157,'Group Loan Item Management Permissions','Group Loan Item Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(158,'Group Loan Item Management Permissions','Group Loan Item Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(159,'Loan Revision Management Permissions','Loan Revision Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(160,'Loan Revision Management Permissions','Loan Revision Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(161,'Loan Revision Management Permissions','Loan Revision Approve','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(162,'Loan Revision Management Permissions','Loan Revision Reject','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(163,'Loan Revision Management Permissions','Loan Revision Cancel','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(164,'Loan Application Guarantor Management Permissions','Loan Application Guarantor Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(165,'Loan Application Guarantor Management Permissions','Loan Application Guarantor Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(166,'Loan Application Guarantor Management Permissions','Loan Application Guarantor Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(167,'Loan Application Guarantor Management Permissions','Loan Application Guarantor Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(168,'Loan Application Customer Management Permissions','Loan Application Customer Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(169,'Loan Application Customer Management Permissions','Loan Application Customer Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(170,'Loan Application Customer Management Permissions','Loan Application Customer Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(171,'Loan Application Customer Management Permissions','Loan Application Customer Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(172,'Loan Application Fixed Asset Management Permissions','Loan Application Fixed Asset Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(173,'Loan Application Fixed Asset Management Permissions','Loan Application Fixed Asset Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(174,'Loan Application Fixed Asset Management Permissions','Loan Application Fixed Asset Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(175,'Loan Application Moving Asset Management Permissions','Loan Application Moving Asset Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(176,'Loan Application Moving Asset Management Permissions','Loan Application Moving Asset Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(177,'Loan Application Moving Asset Management Permissions','Loan Application Moving Asset Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(178,'Loan Application Liability Management Permissions','Loan Application Liability Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(179,'Loan Application Liability Management Permissions','Loan Application Liability Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(180,'Loan Application Liability Management Permissions','Loan Application Liability Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(181,'Loan Application Bank Detail Management Permissions','Loan Application Bank Detail Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(182,'Loan Application Bank Detail Management Permissions','Loan Application Bank Detail Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(183,'Loan Application Bank Detail Management Permissions','Loan Application Bank Detail Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(184,'Loan Installment Management Permissions','Loan Installment Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(185,'Loan Installment Management Permissions','Loan Installment Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(186,'Loan Installment Management Permissions','Loan Installment Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(187,'Loan Installment Management Permissions','Loan Installment Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(188,'Payment Management Permissions','Payment Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(189,'Payment Management Permissions','Payment Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(190,'Payment Management Permissions','Payment Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(191,'Payment Management Permissions','Payment Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(192,'Recovery Case Management Permissions','Recovery Case Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(193,'Recovery Case Management Permissions','Recovery Case Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(194,'Recovery Case Management Permissions','Recovery Case Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(195,'Recovery Case Management Permissions','Recovery Case Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(196,'Recovery Activity Management Permissions','Recovery Activity Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(197,'Recovery Activity Management Permissions','Recovery Activity Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(198,'Recovery Activity Management Permissions','Recovery Activity Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(199,'Recovery Activity Management Permissions','Recovery Activity Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(200,'Recovery Agent Management Permissions','Recovery Agent Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(201,'Recovery Agent Management Permissions','Recovery Agent Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(202,'Recovery Agent Management Permissions','Recovery Agent Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(203,'Recovery Agent Management Permissions','Recovery Agent Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(204,'External Recovery Agent Management Permissions','External Recovery Agent Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(205,'External Recovery Agent Management Permissions','External Recovery Agent Create','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(206,'External Recovery Agent Management Permissions','External Recovery Agent Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(207,'External Recovery Agent Management Permissions','External Recovery Agent Delete','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(208,'Notification Management Permissions','Notification Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(209,'System Settings Permissions','Setting Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(210,'System Settings Permissions','Setting Update','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(211,'Credit Score Management Permissions','Credit Score Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(212,'Credit Score Management Permissions','Credit Score Recompute','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(213,'Admin Dashboard Management Permissions','Admin Dashboard Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(214,'Report Management Permissions','Report Index','api','2026-09-25 06:22:55','2026-09-25 06:22:55'),(215,'Legal Management Permissions','Legal Document Sign','api','2026-10-02 09:10:37','2026-10-02 09:10:37'),(216,'Legal Management Permissions','Legal Document Clear Signatures','api','2026-10-02 09:10:37','2026-10-02 09:10:37'),(220,'Loan Security Permissions','use_plan_1','api','2026-10-05 09:59:39','2026-10-05 09:59:39'),(221,'Loan Security Permissions','use_plan_2','api','2026-10-05 09:59:39','2026-10-05 09:59:39'),(222,'Loan Security Permissions','use_plan_3','api','2026-10-05 09:59:39','2026-10-05 09:59:39'),(223,'Signature Management Permissions','Signature Index','api','2026-10-07 10:31:05','2026-10-07 10:31:05'),(224,'Signature Management Permissions','Signature Create','api','2026-10-07 10:31:05','2026-10-07 10:31:05'),(225,'Signature Management Permissions','Signature Delete','api','2026-10-07 10:31:05','2026-10-07 10:31:05'),(226,'Loan Security Permissions','use_cdp_inv_001','api','2026-10-07 10:31:06','2026-10-07 10:31:06'),(227,'Loan Security Permissions','use_cdp_pro_001','api','2026-10-07 10:31:06','2026-10-07 10:31:06'),(228,'Loan Security Permissions','use_cdp_vec_001','api','2026-10-07 10:31:06','2026-10-07 10:31:06');
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
INSERT INTO `provinces` VALUES (1,'Western Province','PROV-WP',1,1,'2026-09-25 06:28:50','2026-09-25 06:28:50');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recovery_agents`
--

LOCK TABLES `recovery_agents` WRITE;
/*!40000 ALTER TABLE `recovery_agents` DISABLE KEYS */;
INSERT INTO `recovery_agents` VALUES (1,5,1,1,'Dummy recovery agent (password: password)','2026-09-25 07:16:00','2026-09-25 07:16:00'),(2,6,1,1,'Dummy recovery agent (password: password)','2026-09-25 07:16:00','2026-09-25 07:16:00'),(3,7,1,1,'Dummy recovery agent (password: password)','2026-09-25 07:16:00','2026-09-25 07:16:00');
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
INSERT INTO `regions` VALUES (1,'Colombo Region','REG-CMB',1,1,'2026-09-25 06:28:50','2026-09-25 06:28:50');
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
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(39,1),(40,1),(41,1),(42,1),(43,1),(44,1),(45,1),(46,1),(47,1),(48,1),(49,1),(50,1),(51,1),(52,1),(53,1),(54,1),(55,1),(56,1),(57,1),(58,1),(59,1),(60,1),(61,1),(62,1),(63,1),(64,1),(65,1),(66,1),(67,1),(68,1),(69,1),(70,1),(71,1),(72,1),(73,1),(74,1),(75,1),(76,1),(77,1),(78,1),(79,1),(80,1),(81,1),(82,1),(83,1),(84,1),(85,1),(86,1),(87,1),(88,1),(89,1),(90,1),(91,1),(92,1),(93,1),(94,1),(95,1),(96,1),(97,1),(98,1),(99,1),(100,1),(101,1),(102,1),(103,1),(104,1),(105,1),(106,1),(107,1),(108,1),(109,1),(110,1),(111,1),(112,1),(113,1),(114,1),(115,1),(116,1),(117,1),(118,1),(119,1),(120,1),(121,1),(122,1),(123,1),(124,1),(125,1),(126,1),(127,1),(128,1),(129,1),(130,1),(131,1),(132,1),(133,1),(134,1),(135,1),(136,1),(137,1),(138,1),(139,1),(140,1),(141,1),(142,1),(143,1),(144,1),(145,1),(146,1),(147,1),(148,1),(149,1),(150,1),(151,1),(152,1),(153,1),(154,1),(155,1),(156,1),(157,1),(158,1),(159,1),(160,1),(161,1),(162,1),(163,1),(164,1),(165,1),(166,1),(167,1),(168,1),(169,1),(170,1),(171,1),(172,1),(173,1),(174,1),(175,1),(176,1),(177,1),(178,1),(179,1),(180,1),(181,1),(182,1),(183,1),(184,1),(185,1),(186,1),(187,1),(188,1),(189,1),(190,1),(191,1),(192,1),(193,1),(194,1),(195,1),(196,1),(197,1),(198,1),(199,1),(200,1),(201,1),(202,1),(203,1),(204,1),(205,1),(206,1),(207,1),(208,1),(209,1),(210,1),(211,1),(212,1),(213,1),(214,1),(215,1),(216,1),(220,1),(221,1),(222,1),(223,1),(224,1),(225,1),(226,1),(227,1),(228,1),(208,2),(213,2),(73,3),(74,3),(75,3),(76,3);
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','api',0,'2026-09-25 06:22:55','2026-09-25 06:22:55'),(2,'Employee','api',0,'2026-09-25 06:22:55','2026-09-25 06:22:55'),(3,'Staff','api',0,'2026-09-25 07:56:15','2026-09-25 07:56:15');
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'installment_due_period_days','30','integer','loan_recovery','Days after an installment due date before it is marked overdue (used when the loan product has no grace_period_days set).','2026-09-25 06:22:51','2026-09-25 06:22:51'),(2,'overdue_sms_frequency_days','7','integer','loan_recovery','How often (in days) to send an SMS reminder to a customer with an overdue installment.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(3,'overdue_sms_duration_weeks','3','integer','loan_recovery','How many weeks after becoming overdue to keep sending overdue SMS reminders.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(4,'internal_recovery_threshold_days','30','integer','loan_recovery','Days overdue at which a loan application is automatically escalated to internal recovery.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(5,'external_recovery_threshold_days','45','integer','loan_recovery','Days overdue at which an internal recovery case is automatically escalated to external recovery.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(6,'sms_notifications_enabled','1','boolean','loan_recovery','Master switch for overdue SMS notifications.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(7,'recovery_escalation_enabled','1','boolean','loan_recovery','Master switch for automatic recovery case escalation (internal/external).','2026-09-25 06:22:51','2026-09-25 06:22:51'),(8,'customer_bank_list','[\"Commercial Bank\",\"DFCC Bank\",\"HNB\",\"HDFC Bank\",\"NSB\",\"NTB\",\"NDB\",\"Pan Asia Bank\",\"People\'s Bank\",\"Sampath Bank\"]','json','customer','Bank names offered in the customer bank details dropdown.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(9,'loan_revision_enabled','1','boolean','loan_revision','Master switch for the loan revision (restructuring) feature.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(10,'loan_revision_allowed_types','[\"reduce_installment\",\"extend_term\",\"principal_only\"]','json','loan_revision','Revision types an officer may create for a loan application.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(11,'group_loan_competency','[\"Entrepreneurship\"]','json','group_loan','Competency options offered for a Group Loan application\'s competency field — the submitted value must match one of these.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(12,'group_loan_service_charge_percentage','10','decimal','group_loan','Service charge percentage applied to a Group Loan\'s principal in place of interest, snapshotted onto the application at submission time.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(13,'loan_approval_segregation_enabled','1','boolean','loan_approval','Require review, verify and approve to be performed by three different users. Turn off only where one officer legitimately handles the whole file (a very small branch), since it removes the maker-checker control.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(14,'credit_score_enabled','1','boolean','credit_score','Master switch for repayment credit scoring. Turning it off stops all recomputation; scores already stored are left untouched rather than wiped, so switching it back on resumes from where it stopped.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(15,'credit_score_on_time_points','10','integer','credit_score','Points added to the score for each installment settled on or before its due date (plus the credit score grace days).','2026-09-25 06:22:51','2026-09-25 06:22:51'),(16,'credit_score_late_penalty_points','10','integer','credit_score','Points taken off the score for each installment settled late, or still unpaid past its grace period. Enter a positive number; it is subtracted. Two days late and ninety days late cost the same.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(17,'credit_score_grace_days','0','integer','credit_score','Days after the due date a payment may still arrive and count as on time. Separate from the recovery grace period, which decides when a loan turns overdue and is charged a penalty.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(18,'max_loans_per_guarantor','1','integer','guarantor','How many live loans one person may stand guarantor for. Counted by ID number across every customer, so the same person is one guarantor no matter how many times they have been entered. Loans that are rejected, cancelled or closed release their guarantors and stop counting. Set 0 for no limit.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(19,'credit_score_count_unpaid_overdue','1','boolean','credit_score','Deduct points as soon as an installment passes its grace period unpaid, instead of waiting until it is eventually paid. Turn this off and a borrower who simply never pays would never lose a point.','2026-09-25 06:22:51','2026-09-25 06:22:51'),(20,'cdp_investment_max_loan_percentage','50','decimal','loan_security','The most a loan secured by a CDP Investment can be, as a percentage of the investment value. The percentage configured here is applied to the value CDP Core reports for the policy. Several investments may be pledged to one loan; their ceilings are added together.','2026-10-02 05:48:19','2026-10-05 08:49:49'),(25,'property_mortgage_max_loan_percentage','70','decimal','loan_security','The most a loan secured by a Property Mortgage can be, as a percentage of the property valuation. At 70, a property valued at 10,000,000 secures a loan of up to 7,000,000. Several properties may be pledged to one loan; their ceilings are added together.','2026-10-05 08:49:49','2026-10-05 08:49:49'),(26,'vehicle_max_loan_percentage','70','decimal','loan_security','The most a loan secured by a Vehicle can be, as a percentage of the vehicle value. At 70, a vehicle valued at 3,000,000 secures a loan of up to 2,100,000. Several vehicles may be pledged to one loan; their ceilings are added together.','2026-10-05 08:49:49','2026-10-05 08:49:49'),(30,'cdp_investment_security_plans','{\"plan_1\":{\"label\":\"Plan 1\",\"percentage\":50}}','json','loan_security','Lending plans available for this security type. Each plan carries its own percentage of the security value and its own permission (use_<plan code>), so the percentage an officer lends under is chosen from configuration rather than fixed in code. Plan 1 was seeded with the percentage this type already used.','2026-10-05 09:59:39','2026-10-05 09:59:39'),(31,'property_mortgage_security_plans','{\"plan_1\":{\"label\":\"Plan 1\",\"percentage\":70}}','json','loan_security','Lending plans available for this security type. Each plan carries its own percentage of the security value and its own permission (use_<plan code>), so the percentage an officer lends under is chosen from configuration rather than fixed in code. Plan 1 was seeded with the percentage this type already used.','2026-10-05 09:59:39','2026-10-05 09:59:39'),(32,'vehicle_security_plans','{\"plan_1\":{\"label\":\"Plan 1\",\"percentage\":70}}','json','loan_security','Lending plans available for this security type. Each plan carries its own percentage of the security value and its own permission (use_<plan code>), so the percentage an officer lends under is chosen from configuration rather than fixed in code. Plan 1 was seeded with the percentage this type already used.','2026-10-05 09:59:39','2026-10-05 09:59:39');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signatures`
--

DROP TABLE IF EXISTS `signatures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `signatures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `signature_data` json NOT NULL,
  `signed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `signatures_customer_id_foreign` (`customer_id`),
  CONSTRAINT `signatures_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signatures`
--

LOCK TABLES `signatures` WRITE;
/*!40000 ALTER TABLE `signatures` DISABLE KEYS */;
INSERT INTO `signatures` VALUES (2,2,'{\"lines\": [[{\"x\": 45.2, \"y\": 100}, {\"x\": 46.5, \"y\": 102.1}, {\"x\": 50, \"y\": 105}], [{\"x\": 80, \"y\": 120}, {\"x\": 85, \"y\": 130}]], \"width\": 500, \"height\": 250}','2026-10-07 10:43:50','2026-10-07 10:43:50','2026-10-07 10:43:50');
/*!40000 ALTER TABLE `signatures` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Development Admin','devadmin','dev@localhost.com','$2y$12$IpcgRK1q8EJjCrj04GYRvub6nlpe4ewoJintLi95WeeABmU2ejhz2','2026-09-25 06:30:11',NULL,NULL,NULL,'admin',NULL,NULL,1,1,'2026-10-09 04:04:35','127.0.0.1',NULL,'2026-09-25 06:22:56','2026-10-09 04:04:35'),(2,'System','system',NULL,'$2y$12$bEIlpqrELUXldXCWpoPZY.UJHbWFaopiw9P7hP6sN8Lk3b6jwmdg.',NULL,NULL,NULL,NULL,'admin',NULL,NULL,0,0,NULL,NULL,NULL,'2026-09-25 06:22:56','2026-09-25 06:22:56'),(3,'Sulani Pabodha','Sulani','sulanihost@gmail.com','$2y$12$iDpsJu07rp4TTcVyIsQMEOtsMKyIm6rBqHmhgEN9xivecXf0SZC6i',NULL,'2026-09-28 06:34:52',NULL,NULL,'customer',NULL,1,1,1,NULL,NULL,NULL,'2026-09-25 06:34:52','2026-09-25 06:34:52'),(4,'Piranya Paskaran','Piranya','piranya@gmail.com','$2y$12$ireACK0KHbEIR.wDoexiC.Y5c5HJHnu/BQE40ihs1m2RG0sbFWhly',NULL,'2026-09-28 07:00:47',NULL,NULL,'customer',NULL,2,1,1,NULL,NULL,NULL,'2026-09-25 07:00:47','2026-09-25 07:00:47'),(5,'Kumar Rajendran','agent.kumar','kumar.agent@cdp.lk','$2y$12$At3t2ZuYlS4qEW0p9lIIJOirsMKc663Y/Bn.fzle2O9YbJE52rEKK',NULL,NULL,NULL,NULL,'staff',NULL,NULL,1,1,NULL,NULL,NULL,'2026-09-25 07:16:00','2026-09-25 07:16:00'),(6,'Nilani Fernando','agent.nilani','nilani.agent@cdp.lk','$2y$12$O28IzJt4.gc0yHGduDIZgeMmKGC3XhyPKHAQZQxJOCSUZ/pTrQETi',NULL,NULL,NULL,NULL,'staff',NULL,NULL,1,1,NULL,NULL,NULL,'2026-09-25 07:16:00','2026-09-25 07:16:00'),(7,'Suresh Yogarajah','agent.suresh','suresh.agent@cdp.lk','$2y$12$XkvCCOGNc7kjKdMIFCYpbud.3pGTAYhsF78YxynJfkh0wbWocbrau',NULL,NULL,NULL,NULL,'staff',NULL,NULL,1,1,NULL,NULL,NULL,'2026-09-25 07:16:00','2026-09-25 07:16:00'),(8,'Pathma','EMP-NUG-001','sulani98@gmail.com','$2y$12$L.lxPuTFpJ6CJ64vP5cLtO.5p16xSldSMS7WK3M1CGSUi2lC41lm6','2026-09-25 07:54:47',NULL,NULL,NULL,'staff',1,NULL,1,1,'2026-09-25 07:56:52','127.0.0.1',NULL,'2026-09-25 07:54:47','2026-09-25 07:56:52'),(9,'Thipanujan Raja','thipanujan','vedhasiricdp@gmail.com','$2y$12$5kYOvH90j2t.wADe4wnfNe30oO93TaQzD21i/fWaJuKtKDNIWNeB2','2026-09-28 05:53:10',NULL,NULL,'2026-09-28 04:55:19','customer',NULL,3,1,1,'2026-09-28 05:53:31','127.0.0.1',NULL,'2026-09-28 04:53:49','2026-09-28 05:53:31');
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
INSERT INTO `zonals` VALUES (1,'Colombo Zone','ZON-CMB',1,1,'2026-09-25 06:28:50','2026-09-25 06:28:50');
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

-- Dump completed on 2026-10-09  9:37:54
