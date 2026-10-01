curl --location --request GET 'https://developers.hotmart.com/club/api/v1/users?subdomain=my-subdomain' \
	--header 'Content-Type: application/json' \
	--header 'Authorization: Bearer :access_token'

  {
  "items": [
    {
      "user_id": "n2OM623n46",
      "engagement": "NONE",
      "name": "Hotmart Example User One",
      "email": "user.one@hotmart.com",
      "last_access_date": 1546728645,
      "role": "FREE_STUDENT",
      "first_access_date": 1607054711,
      "locale": "pt_BR",
      "plus_access": "WITHOUT_PLUS_ACCESS",
      "progress": {
        "completed_percentage": 45,
        "total": 11,
        "completed": 5
      },
      "status": "ACTIVE",
      "access_count": 1,
      "is_deletable": true,
      "class_id": "qV7y1Jm7Jn",
      "type": "FREE"
    },
    {
      "user_id": "ZYOmWXlded",
      "engagement": "LOW",
      "name": "Hotmart Example User Two",
      "email": "user.two@hotmart.com",
      "last_access_date": 1819975825,
      "role": "STUDENT",
      "first_access_date": 1532627687,
      "locale": "pt_BR",
      "plus_access": "WITHOUT_PLUS_ACCESS",
      "progress": {
        "completed_percentage": 0,
        "total": 11,
        "completed": 0
      },
      "status": "ACTIVE",
      "purchase_date": 1616501263,
      "access_count": 2,
      "is_deletable": true,
      "class_id": "qV7y1Jm7Jn",
      "type": "BUYER"
    },
    {
      "user_id": "wx7WpWrQO2",
      "engagement": "MEDIUM",
      "name": "Hotmart Example User Three",
      "email": "user.three@hotmart.com",
      "last_access_date": 1278881901,
      "role": "STUDENT",
      "first_access_date": 1607054711,
      "locale": "pt_BR",
      "plus_access": "WITHOUT_PLUS_ACCESS",
      "progress": {
        "completed_percentage": 0,
        "total": 11,
        "completed": 0
      },
      "status": "BLOCKED",
      "purchase_date": 1616501263,
      "access_count": 1,
      "is_deletable": true,
      "class_id": "qV7y1Jm7Jn",
      "type": "IMPORTED"
    }
  ],
  "page_info": {
    "total_results": 111,
    "next_page_token": "eyJwYWdlIjoyLCJyb3dzIjoxMH0=",
    "prev_page_token": "eyJwYWdlIjoyLCJyb3dzIjoxMH0=",
    "results_per_page": 3
  }
}

curl --location --request GET 'https://developers.hotmart.com/products/api/v1/products' \
 --header 'Content-Type: application/json' \
 --header 'Authorization: Bearer :access_token'

 {
 "items": [
  {
    "id": 698441,
    "name": "Product A",
    "ucode": "f2b3be1f-313f-4a2d-b5b7-1c39d67dd3ee",
    "status": "DRAFT",
    "created_at": 1586459699000,
    "format": "EBOOK",
    "is_subscription": false,
    "warranty_period": 7
  },
  {
    "id": 1117869,
    "name": "Product B",
    "ucode": "26a97448-2ac2-458d-9e03-bcc01e82bdd8",
    "status": "DRAFT",
    "created_at": 1603816477000,
    "format": "ONLINE_COURSE",
    "is_subscription": true,
    "warranty_period": 15
  },
  {
   "id": 486869,
   "name": "Product C",
   "ucode": "6505e7ed-ff32-4d1a-8baa-62958d5c790a",
   "status": "CHANGES_PENDING_ON_PRODUCT",
   "created_at": 1569933453000,
   "format": "ETICKET",
   "is_subscription": false,
   "warranty_period": 7
  },
  {
    "id": 4319408,
    "name": "Product D",
    "ucode": "e211d636-dd19-4411-9397-ab3428e966a2",
    "status": "DRAFT",
    "created_at": 1721077570000,
    "format": "BUNDLE",
    "is_subscription": true,
    "warranty_period": 7
  }
 ],
 "page_info": {
  "next_page_token": "eyJyb3dzIjo1LCJwYWdlIjozfQ==",
  "prev_page_token": "eyJyb3dzIjo1LCJwYWdlIjoxfQ==",
  "results_per_page": 4
 }
}

curl --location --request GET 'https://developers.hotmart.com/payments/api/v1/sales/history?transaction_status=APPROVED' \
	--header 'Content-Type: application/json' \
	--header 'Authorization: Bearer :access_token'

  {
  "items": [
    {
      "product": {
        "name": "Product06",
        "id": 2125812
      },
      "buyer": {
        "name": "Ian Victor Baptista",
        "ucode": "839F1A4F-43DC-F60F-13FE-6C8BD23F6781",
        "email": "ian@teste.com"
      },
      "producer": {
        "name": "Bárbara Sebastiana Cardoso",
        "ucode": "252A74C5-4A97-143A-9349-E45D871C6018"
      },
      "purchase": {
        "transaction": "HP12455690122399",
        "order_date": 1622948400000,
        "approved_date": 1622948400000,
        "status": "UNDER_ANALISYS",
        "recurrency_number": 2,
        "is_subscription": false,
        "commission_as": "PRODUCER",
        "price": {
          "value": 235.76,
          "currency_code": "USD"
        },
        "payment": {
          "method": "BILLET",
          "installments_number": 1,
          "type": "BILLET"
        },
        "tracking": {
          "source_sck": "HOTMART_PRODUCT_PAGE",
          "source": "HOTMART",
          "external_code": "FD256D24-401C-7C93-284C-C5E0181CD5DB"
        },
        "warranty_expire_date": 1625022000000,
        "offer": {
          "payment_mode": "INVOICE",
          "code": "k2pasun0"
        },
        "hotmart_fee": {
          "total": 36.75,
          "fixed": 0,
          "currency_code": "EUR",
          "base": 11.12,
          "percentage": 9.9
        }
      }
    }
  ],
  "page_info": {
    "total_results": 14,
    "next_page_token": "eyJyb3dzIjo1LCJwYWdlIjozfQ==",
    "prev_page_token": "eyJyb3dzIjo1LCJwYWdlIjoxfQ==",
    "results_per_page": 5
  }
}