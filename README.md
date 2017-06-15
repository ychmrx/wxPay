厦门点媒网络科技有限公司
微信扫码支付

安装
composer require yinjiang/wxpay

配置参数在config/config.php

使用步骤
1.调用getQrCode()函数，传入订单号，订单金额，订单描述，获取二维码链接，直接<img src="链接"/>生成二维码图片
2.调用doResolveResult函数，获取微信返回的数组结果
3.调用getOrderQuery函数，查询是否支付成功
