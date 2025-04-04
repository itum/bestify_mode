<?php $content=file_get_contents("index.php"); $insert_at="        $price_format = number_format($original_price);
        $discount_format = number_format($discountedPrice);
        $balance_format = is_numeric($user[\"Balance\"]) ? number_format($user[\"Balance\"]) : 0;"; $insert_content="        $price_format = number_format($original_price);
        $discount_format = number_format($discountedPrice);
        $balance_format = is_numeric($user[\"Balance\"]) ? number_format($user[\"Balance\"]) : 0;

        // متن فاکتور برای نمایندگان
        $textin = sprintf($textbotlang[\"users\"][\"buy\"][\"invoicebuy-agent\"],
            $username_ac,
            $info_product[\"name_product\"],
            $info_product[\"Service_time\"],
            $price_format,
            $agencyDiscount,
            $discount_format,
            $info_product[\"Volume_constraint\"],
            $balance_format
        );"; $content=str_replace($insert_at, $insert_content, $content); file_put_contents("index.php", $content); echo "Done";
