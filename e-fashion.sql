-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-04-18 12:50:53
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `e-fashion`
--

-- --------------------------------------------------------

--
-- 表的结构 `01_admin`
--

CREATE TABLE `01_admin` (
  `AdminID` int(10) NOT NULL,
  `Admin_Name` varchar(30) NOT NULL,
  `Admin_Password` varchar(255) DEFAULT NULL,
  `Admin_Email` varchar(30) NOT NULL,
  `Admin_Username` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `01_admin`
--

INSERT INTO `01_admin` (`AdminID`, `Admin_Name`, `Admin_Password`, `Admin_Email`, `Admin_Username`) VALUES
(12345678, 'hallo', 'abc1234', 'jsbdans@gmial.com.my', 'hhaas'),
(98765432, 'New Admin', '$2y$10$UUQIm/N.1S2/ofQOddA8/ubRm5tMkvX6jLQYMaqdF32.zxh0zqQwC', 'admin2025@example.com', 'admin2025');

-- --------------------------------------------------------

--
-- 表的结构 `02_customer`
--

CREATE TABLE `02_customer` (
  `CustomerID` int(10) NOT NULL,
  `Cust_First_Name` varchar(30) NOT NULL,
  `Cust_Last_Name` varchar(30) NOT NULL,
  `Cust_Address` varchar(30) NOT NULL,
  `Cust_City` varchar(10) NOT NULL,
  `Cust_Postcode` int(5) NOT NULL,
  `Cust_State` varchar(15) NOT NULL,
  `Cust_Email` varchar(30) NOT NULL,
  `Cust_Password` varchar(255) NOT NULL,
  `Cust_Username` varchar(30) DEFAULT NULL,
  `Cust_PhoneNumber` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `02_customer`
--

INSERT INTO `02_customer` (`CustomerID`, `Cust_First_Name`, `Cust_Last_Name`, `Cust_Address`, `Cust_City`, `Cust_Postcode`, `Cust_State`, `Cust_Email`, `Cust_Password`, `Cust_Username`, `Cust_PhoneNumber`) VALUES
(13, 'CHAN', 'SHENG', 'No 3 , Jalan Puteri 10', 'Tangkak', 84020, 'Johor', 'chan.chin.sheng.05@gmail.com', '$2y$10$KVhOFXu2S.51GNzzQcVyxOsGwy.rhyEiPP8gdXy5ggHvWMxBmbD4O', 'dsdfsdfs', 182942536);

-- --------------------------------------------------------

--
-- 表的结构 `03_brand`
--

CREATE TABLE `03_brand` (
  `BrandID` int(10) NOT NULL,
  `BrandName` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `03_brand`
--

INSERT INTO `03_brand` (`BrandID`, `BrandName`) VALUES
(1, 'Alain Delon');

-- --------------------------------------------------------

--
-- 表的结构 `04_category`
--

CREATE TABLE `04_category` (
  `CategoryID` int(10) NOT NULL,
  `CategoryName` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `04_category`
--

INSERT INTO `04_category` (`CategoryID`, `CategoryName`) VALUES
(1, '手表');

-- --------------------------------------------------------

--
-- 表的结构 `05_product`
--

CREATE TABLE `05_product` (
  `ProductID` int(10) NOT NULL,
  `ProductName` varchar(30) NOT NULL,
  `Product_Price` double NOT NULL,
  `Product_Description` varchar(1500) NOT NULL,
  `Product_Image` varchar(255) DEFAULT NULL COMMENT 'Product Image URL',
  `Product_Stock_Quantity` int(10) NOT NULL,
  `Product_Status` varchar(20) NOT NULL,
  `CategoryID` int(10) NOT NULL,
  `BrandID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `05_product`
--

INSERT INTO `05_product` (`ProductID`, `ProductName`, `Product_Price`, `Product_Description`, `Product_Image`, `Product_Stock_Quantity`, `Product_Status`, `CategoryID`, `BrandID`) VALUES
(100024578, 'Alain Delon Chronograph', 399, '精美设计的计时码表，搭配绿色表盘与黑色真皮表带。', 'images/alain_delon_watch.jpg', 111, 'Available', 1, 1);

-- --------------------------------------------------------

--
-- 表的结构 `06_tracking`
--

CREATE TABLE `06_tracking` (
  `TrackingID` int(10) NOT NULL,
  `Tracking_Number` varchar(11) NOT NULL,
  `Delivery_Status` varchar(20) NOT NULL,
  `Delivery_Address` varchar(30) NOT NULL,
  `Delivery_City` varchar(10) NOT NULL,
  `Delivery_Postcode` int(5) NOT NULL,
  `Delivery_State` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `06_tracking`
--

INSERT INTO `06_tracking` (`TrackingID`, `Tracking_Number`, `Delivery_Status`, `Delivery_Address`, `Delivery_City`, `Delivery_Postcode`, `Delivery_State`) VALUES
(1, 'OZ5UTAM874F', '准备中', 'No 3 , Jalan Puteri 10', 'Tangkak', 84020, 'Johor'),
(2, '1H063DGBFTC', '准备中', 'No 3 , Jalan Puteri 10', 'Tangkak', 84020, 'Johor'),
(3, '7AQ9IJV36LH', '准备中', 'No 3 , Jalan Puteri 10', 'Tangkak', 84020, 'Johor');

-- --------------------------------------------------------

--
-- 表的结构 `07_order`
--

CREATE TABLE `07_order` (
  `OrderID` int(10) NOT NULL,
  `OrderDate` date NOT NULL,
  `OrderStatus` varchar(20) NOT NULL,
  `TrackingID` int(10) NOT NULL,
  `Total_Price` double NOT NULL,
  `Shipping_Method` varchar(50) NOT NULL DEFAULT 'Standard Delivery (Malaysia)',
  `Shipping_Name` varchar(60) DEFAULT NULL,
  `Shipping_Address` varchar(100) DEFAULT NULL,
  `Shipping_City` varchar(30) DEFAULT NULL,
  `Shipping_Postcode` varchar(10) DEFAULT NULL,
  `Shipping_State` varchar(30) DEFAULT NULL,
  `Shipping_Phone` varchar(20) DEFAULT NULL,
  `CustomerID` int(10) DEFAULT NULL,
  `Is_Guest` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `07_order`
--

INSERT INTO `07_order` (`OrderID`, `OrderDate`, `OrderStatus`, `TrackingID`, `Total_Price`, `Shipping_Method`, `Shipping_Name`, `Shipping_Address`, `Shipping_City`, `Shipping_Postcode`, `Shipping_State`, `Shipping_Phone`, `CustomerID`, `Is_Guest`) VALUES
(3, '2025-04-18', 'pending', 1, 21546, 'Standard Delivery (Malaysia)', 'CHAN CHIN SHENG', 'No 3 , Jalan Puteri 10', 'Tangkak', '84020', 'Johor', '0182942536', NULL, 0),
(4, '2025-04-18', 'pending', 2, 31122, 'Standard Delivery (Malaysia)', 'CHAN CHIN SHENG', 'No 3 , Jalan Puteri 10', 'Tangkak', '84020', 'Johor', '0182942536', NULL, 0),
(5, '2025-04-18', 'pending', 3, 9975, 'Standard Delivery (Malaysia)', 'JEFF CHAN', 'No 3 , Jalan Puteri 10', 'Tangkak', '84020', 'Johor', '0182942536', 13, 0);

-- --------------------------------------------------------

--
-- 表的结构 `08_order_details`
--

CREATE TABLE `08_order_details` (
  `Order_detailsID` int(10) NOT NULL,
  `OrderID` int(10) NOT NULL,
  `ProductID` int(10) NOT NULL,
  `Order_Quantity` int(11) NOT NULL,
  `Order_Subtotal` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `08_order_details`
--

INSERT INTO `08_order_details` (`Order_detailsID`, `OrderID`, `ProductID`, `Order_Quantity`, `Order_Subtotal`) VALUES
(3, 5, 100024578, 25, 9975);

-- --------------------------------------------------------

--
-- 表的结构 `09_payment`
--

CREATE TABLE `09_payment` (
  `PaymentID` int(10) NOT NULL,
  `OrderID` int(10) NOT NULL,
  `Payment_Card_Type` varchar(50) NOT NULL,
  `Payment_Card_Number` int(50) NOT NULL,
  `Payment_Card_Bank` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `09_payment`
--

INSERT INTO `09_payment` (`PaymentID`, `OrderID`, `Payment_Card_Type`, `Payment_Card_Number`, `Payment_Card_Bank`) VALUES
(3, 5, 'Visa', 1234, 'Maybank');

-- --------------------------------------------------------

--
-- 表的结构 `10_guest_address`
--

CREATE TABLE `10_guest_address` (
  `AddressID` int(10) NOT NULL,
  `Guest_Email` varchar(30) NOT NULL,
  `First_Name` varchar(30) NOT NULL,
  `Last_Name` varchar(30) NOT NULL,
  `Address` varchar(100) NOT NULL,
  `City` varchar(30) NOT NULL,
  `Postcode` varchar(10) NOT NULL,
  `State` varchar(30) NOT NULL,
  `Phone` varchar(20) NOT NULL,
  `Is_Default` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `11_cart`
--

CREATE TABLE `11_cart` (
  `CartID` int(11) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `11_cart`
--

INSERT INTO `11_cart` (`CartID`, `CustomerID`, `CreatedAt`) VALUES
(45, 13, '2025-04-18 10:37:14');

-- --------------------------------------------------------

--
-- 表的结构 `12_cart_item`
--

CREATE TABLE `12_cart_item` (
  `CartItemID` int(11) NOT NULL,
  `CartID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1,
  `AddedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `01_admin`
--
ALTER TABLE `01_admin`
  ADD PRIMARY KEY (`AdminID`);

--
-- 表的索引 `02_customer`
--
ALTER TABLE `02_customer`
  ADD PRIMARY KEY (`CustomerID`);

--
-- 表的索引 `03_brand`
--
ALTER TABLE `03_brand`
  ADD PRIMARY KEY (`BrandID`);

--
-- 表的索引 `04_category`
--
ALTER TABLE `04_category`
  ADD PRIMARY KEY (`CategoryID`);

--
-- 表的索引 `05_product`
--
ALTER TABLE `05_product`
  ADD PRIMARY KEY (`ProductID`),
  ADD KEY `CategoryID` (`CategoryID`),
  ADD KEY `BrandID` (`BrandID`);

--
-- 表的索引 `06_tracking`
--
ALTER TABLE `06_tracking`
  ADD PRIMARY KEY (`TrackingID`);

--
-- 表的索引 `07_order`
--
ALTER TABLE `07_order`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `TrackingID` (`TrackingID`),
  ADD KEY `fk_order_customer` (`CustomerID`);

--
-- 表的索引 `08_order_details`
--
ALTER TABLE `08_order_details`
  ADD PRIMARY KEY (`Order_detailsID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- 表的索引 `09_payment`
--
ALTER TABLE `09_payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `OrderID` (`OrderID`);

--
-- 表的索引 `10_guest_address`
--
ALTER TABLE `10_guest_address`
  ADD PRIMARY KEY (`AddressID`),
  ADD KEY `idx_guest_email` (`Guest_Email`);

--
-- 表的索引 `11_cart`
--
ALTER TABLE `11_cart`
  ADD PRIMARY KEY (`CartID`),
  ADD KEY `CustomerID` (`CustomerID`);

--
-- 表的索引 `12_cart_item`
--
ALTER TABLE `12_cart_item`
  ADD PRIMARY KEY (`CartItemID`),
  ADD UNIQUE KEY `unique_cart_product` (`CartID`,`ProductID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `01_admin`
--
ALTER TABLE `01_admin`
  MODIFY `AdminID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98765433;

--
-- 使用表AUTO_INCREMENT `02_customer`
--
ALTER TABLE `02_customer`
  MODIFY `CustomerID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用表AUTO_INCREMENT `03_brand`
--
ALTER TABLE `03_brand`
  MODIFY `BrandID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `04_category`
--
ALTER TABLE `04_category`
  MODIFY `CategoryID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `05_product`
--
ALTER TABLE `05_product`
  MODIFY `ProductID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100024580;

--
-- 使用表AUTO_INCREMENT `06_tracking`
--
ALTER TABLE `06_tracking`
  MODIFY `TrackingID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `07_order`
--
ALTER TABLE `07_order`
  MODIFY `OrderID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `08_order_details`
--
ALTER TABLE `08_order_details`
  MODIFY `Order_detailsID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `09_payment`
--
ALTER TABLE `09_payment`
  MODIFY `PaymentID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `10_guest_address`
--
ALTER TABLE `10_guest_address`
  MODIFY `AddressID` int(10) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `11_cart`
--
ALTER TABLE `11_cart`
  MODIFY `CartID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- 使用表AUTO_INCREMENT `12_cart_item`
--
ALTER TABLE `12_cart_item`
  MODIFY `CartItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- 限制导出的表
--

--
-- 限制表 `05_product`
--
ALTER TABLE `05_product`
  ADD CONSTRAINT `05_product_ibfk_1` FOREIGN KEY (`CategoryID`) REFERENCES `04_category` (`CategoryID`),
  ADD CONSTRAINT `05_product_ibfk_2` FOREIGN KEY (`BrandID`) REFERENCES `03_brand` (`BrandID`);

--
-- 限制表 `07_order`
--
ALTER TABLE `07_order`
  ADD CONSTRAINT `07_order_ibfk_1` FOREIGN KEY (`TrackingID`) REFERENCES `06_tracking` (`TrackingID`),
  ADD CONSTRAINT `fk_order_customer` FOREIGN KEY (`CustomerID`) REFERENCES `02_customer` (`CustomerID`) ON DELETE SET NULL;

--
-- 限制表 `08_order_details`
--
ALTER TABLE `08_order_details`
  ADD CONSTRAINT `08_order_details_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `07_order` (`OrderID`),
  ADD CONSTRAINT `08_order_details_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `05_product` (`ProductID`);

--
-- 限制表 `09_payment`
--
ALTER TABLE `09_payment`
  ADD CONSTRAINT `09_payment_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `07_order` (`OrderID`);

--
-- 限制表 `11_cart`
--
ALTER TABLE `11_cart`
  ADD CONSTRAINT `11_cart_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `02_customer` (`CustomerID`);

--
-- 限制表 `12_cart_item`
--
ALTER TABLE `12_cart_item`
  ADD CONSTRAINT `12_cart_item_ibfk_1` FOREIGN KEY (`CartID`) REFERENCES `11_cart` (`CartID`),
  ADD CONSTRAINT `12_cart_item_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `05_product` (`ProductID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
