create table IF NOT EXISTS b_ipol_dpd_order (
	ID int not null auto_increment,

	ORDER_ID varchar(255) null,
	SHIPMENT_ID int null,

	ORDER_DATE varchar(20) null,
	ORDER_DATE_CREATE varchar(20) null,
	ORDER_DATE_CANCEL varchar(20) null,
	ORDER_DATE_STATUS varchar(20) null,
	ORDER_NUM varchar(15) null,
	ORDER_STATUS text null,
	ORDER_STATUS_CANCEL text null,
	ORDER_ERROR text null,
	
	SERVICE_CODE char(3) null,
	SERVICE_VARIANT char(2) null,

	PICKUP_DATE varchar(20) null,
	PICKUP_TIME_PERIOD char(5) null,
	DELIVERY_TIME_PERIOD char(5) null,

	DIMENSION_WIDTH  double not null default '0',
	DIMENSION_HEIGHT double not null default '0',
	DIMENSION_LENGTH double not null default '0',
	CARGO_VOLUME double not null default '0',
	CARGO_WEIGHT double not null default '0',

	CARGO_NUM_PACK double null,
	CARGO_CATEGORY varchar(255) null,

	SENDER_FIO varchar(255) null,
	SENDER_NAME varchar(255) null,
	SENDER_PHONE varchar(20) null,
	SENDER_LOCATION varchar(255) not null,
	SENDER_STREET varchar(50) null,
	SENDER_STREETABBR varchar(10) null,
	SENDER_HOUSE varchar(10) null,
	SENDER_KORPUS varchar(10) null,
	SENDER_STR varchar(10) null,
	SENDER_VLAD varchar(10) null,	
	SENDER_OFFICE varchar(10) null,
	SENDER_FLAT varchar(10) null,
	SENDER_TERMINAL_CODE char(4) null,
	
	RECEIVER_FIO varchar(255) null,
	RECEIVER_NAME varchar(255) null,
	RECEIVER_PHONE varchar(20) null,
	RECEIVER_LOCATION varchar(255) not null,
	RECEIVER_STREET varchar(50) null,
	RECEIVER_STREETABBR varchar(10) null,
	RECEIVER_HOUSE varchar(10) null,
	RECEIVER_KORPUS varchar(10) null,
	RECEIVER_STR varchar(10) null,
	RECEIVER_VLAD varchar(10) null,	
	RECEIVER_OFFICE varchar(10) null,
	RECEIVER_FLAT varchar(10) null,
	RECEIVER_TERMINAL_CODE char(4) null,
	RECEIVER_COMMENT text null,

	PRICE DOUBLE NULL,
	PRICE_DELIVERY DOUBLE NULL,
	CARGO_VALUE double null,
	NPP char(1) not null default 'N',
	SUM_NPP DOUBLE NULL,
	
	CARGO_REGISTERED char(1) not null default 'N',
	SMS varchar(25) null,
	EML varchar(50) null,
	ESD varchar(50) null,
	ESZ char(50) null,
	OGD char(4) null,
	DVD char(1) not null default 'N',
	VDO char(1) not null default 'N',
	POD varchar(50) null,
	PRD char(1) not null default 'N',
	TRM char(1) not null default 'N',

	LABEL_FILE varchar(255) null,
	INVOICE_FILE varchar(255) null,
	
	ORDER_ITEMS text null,
	PAY_SYSTEM_ID int null,
	PERSONE_TYPE_ID int null,
	CURRENCY varchar(255) null,

	PAYMENT_TYPE varchar(255) null,
	SENDER_EMAIL varchar(50) DEFAULT NULL,
	RECEIVER_EMAIL varchar(50) DEFAULT NULL,
	SENDER_NEED_PASS char(1) DEFAULT 'N',
	RECEIVER_NEED_PASS char(1) DEFAULT 'N',

	UNIT_LOADS text null,
	USE_CARGO_VALUE char(1) not null default 'N',
	USE_MARKING char(1) not null default 'N',

	primary key (ID)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;