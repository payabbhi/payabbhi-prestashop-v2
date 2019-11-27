## Payabbhi Payment Extension for PrestaShop 1.7.x.x

This extension is built on Payabbhi PHP Library to provide seamless integration of [Payabbhi Checkout ](https://payabbhi.com/docs/payments/checkout) with PrestaShop 1.7.x.x


### Installation

Make sure you have signed up for your [Payabbhi Account](https://payabbhi.com/docs/account) and downloaded the [API keys](https://payabbhi.com/docs/account/#api-keys) from the [Portal](https://payabbhi.com/portal).

1. Navigate to `PrestaShop Back Office` -> `Modules` -> `Module Manager` and click on `Upload a module`.

2. Upload `payabbhi.zip` to add Payabbhi Payment Extension to PrestaShop.

3. On successful upload, `payabbhi` folder should get added to PrestaShop installation directory as follows:

  ```
  PrestaShop/
    modules/
      payabbhi/
        config.xml
        controllers/
        index.php
        logo.png
        payabbhi-php/
        payabbhi.php
        views/
        script.js
  ```

4. After successful upload, navigate to `Modules` -> `Module Manager` ->`Other`. If you do not find Payabbhi on the list, please use the search option to find it.

5. Configure `Payabbhi` and Save the settings:
  - [Access ID](https://payabbhi.com/docs/account/#api-keys)
  - [Secret Key](https://payabbhi.com/docs/account/#api-keys)
  - [Payment Auto Capture](https://payabbhi.com/docs/api/#create-an-order)


[Payabbhi Checkout](https://payabbhi.com/docs/payments/checkout) is now enabled in PrestaShop.

### Payabbhi Prestashop Plugin for previous version/s

For Prestashop 1.6.x, refer to [Payabbhi Prestashop 1.6.x Plugin](https://github.com/payabbhi/payabbhi-prestashop)
