[2025-12-10 18:22:01] === WEBHOOK STARTED ===
[2025-12-10 18:22:01] RAW INPUT => {"type":"Order","api_key":null,"products":[{"id":1048634,"name":"TEST","price":"0.00","status":"Charged","shipping":"0.00","tax":"0.00","quantity":1,"coupon":{"amount":"1.00","code":"sam100","description":""}}],"customer":{"first_name":"ved","last_name":"patel","email":"priyacharmin@gmail.com","phone_number":null,"customer_id":21356456,"billing_address":"408-755 10th St W","billing_city":"Owen sound","billing_state":"ON","billing_zip":"N4k 6J7","billing_country":"Canada"},"order":{"created_at":"2025-12-10T13:21:49-05:00","id":23393764,"total":"0.00","ip_address":"209.226.209.134","stripe_id":"cus_TZzdrCW6A0MYJ2","total_tax":"0.00","total_shipping":"0.00","processor":"Stripe","custom_fields":[],"upsell_custom_fields":[],"shipping_address":"408-755 10th St W","shipping_city":"Owen sound","shipping_state":"ON","shipping_zip":"N4k 6J7","shipping_country":"Canada","analytics":{"medium":"ambassador","source":"referral","campaign":"clinicsecret","content":null,"lead_source":null}},"affiliate":[]}
[2025-12-10 18:22:01] JSON-DECODED RAW => Array
(
    [type] => Order
    [api_key] => 
    [products] => Array
        (
            [0] => Array
                (
                    [id] => 1048634
                    [name] => TEST
                    [price] => 0.00
                    [status] => Charged
                    [shipping] => 0.00
                    [tax] => 0.00
                    [quantity] => 1
                    [coupon] => Array
                        (
                            [amount] => 1.00
                            [code] => sam100
                            [description] => 
                        )

                )

        )

    [customer] => Array
        (
            [first_name] => ved
            [last_name] => patel
            [email] => priyacharmin@gmail.com
            [phone_number] => 
            [customer_id] => 21356456
            [billing_address] => 408-755 10th St W
            [billing_city] => Owen sound
            [billing_state] => ON
            [billing_zip] => N4k 6J7
            [billing_country] => Canada
        )

    [order] => Array
        (
            [created_at] => 2025-12-10T13:21:49-05:00
            [id] => 23393764
            [total] => 0.00
            [ip_address] => 209.226.209.134
            [stripe_id] => cus_TZzdrCW6A0MYJ2
            [total_tax] => 0.00
            [total_shipping] => 0.00
            [processor] => Stripe
            [custom_fields] => Array
                (
                )

            [upsell_custom_fields] => Array
                (
                )

            [shipping_address] => 408-755 10th St W
            [shipping_city] => Owen sound
            [shipping_state] => ON
            [shipping_zip] => N4k 6J7
            [shipping_country] => Canada
            [analytics] => Array
                (
                    [medium] => ambassador
                    [source] => referral
                    [campaign] => clinicsecret
                    [content] => 
                    [lead_source] => 
                )

        )

    [affiliate] => Array
        (
        )

)

[2025-12-10 18:22:01] ERROR_INSERTING_RAW_WEBHOOK => SQLSTATE[42S22]: Column not found: 1054 Unknown column 'event_type' in 'INSERT INTO'
[2025-12-10 18:22:01] STEP: NORMALIZATION START => Array
(
    [type] => Order
    [api_key] => 
    [products] => Array
        (
            [0] => Array
                (
                    [id] => 1048634
                    [name] => TEST
                    [price] => 0.00
                    [status] => Charged
                    [shipping] => 0.00
                    [tax] => 0.00
                    [quantity] => 1
                    [coupon] => Array
                        (
                            [amount] => 1.00
                            [code] => sam100
                            [description] => 
                        )

                )

        )

    [customer] => Array
        (
            [first_name] => ved
            [last_name] => patel
            [email] => priyacharmin@gmail.com
            [phone_number] => 
            [customer_id] => 21356456
            [billing_address] => 408-755 10th St W
            [billing_city] => Owen sound
            [billing_state] => ON
            [billing_zip] => N4k 6J7
            [billing_country] => Canada
        )

    [order] => Array
        (
            [created_at] => 2025-12-10T13:21:49-05:00
            [id] => 23393764
            [total] => 0.00
            [ip_address] => 209.226.209.134
            [stripe_id] => cus_TZzdrCW6A0MYJ2
            [total_tax] => 0.00
            [total_shipping] => 0.00
            [processor] => Stripe
            [custom_fields] => Array
                (
                )

            [upsell_custom_fields] => Array
                (
                )

            [shipping_address] => 408-755 10th St W
            [shipping_city] => Owen sound
            [shipping_state] => ON
            [shipping_zip] => N4k 6J7
            [shipping_country] => Canada
            [analytics] => Array
                (
                    [medium] => ambassador
                    [source] => referral
                    [campaign] => clinicsecret
                    [content] => 
                    [lead_source] => 
                )

        )

    [affiliate] => Array
        (
        )

)

[2025-12-10 18:22:01] Payload not recognized or missing event
