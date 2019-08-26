<?php

use \code\Page;
use \code\Model\User;
use \code\PagSeguro\Config;
use \code\PagSeguro\Transporter;
use \code\PagSeguro\Document;
use \code\PagSeguro\Phone;
use \code\PagSeguro\Address;
use \code\PagSeguro\Sender;
use \code\PagSeguro\Shipping;
use \code\PagSeguro\Item;
use \code\PagSeguro\Payment;
use \code\PagSeguro\CreditCard;
use \code\PagSeguro\CreditCard\Installment;
use \code\PagSeguro\CreditCard\Holder;
use \code\PagSeguro\Bank;
use \code\Model\Order;

$app->get('/payment/success', function(){

	User::verifyLogin(false);

	$order = new Order();

	$order->getFromSession();

	$page = new Page();


	$page->setTpl('payment-success',[
		'order'=>$order->getValues()

	]);



});

$app->post('/payment/credit', function(){
    User::verifyLogin(false);
    $order = new Order();
    $order->getFromSession();
    $order->get((int)$order->getidorder());
    $address = $order->getAddress();
    $cart = $order->getCart();
    $cpf = new Document(Document::CPF, $_POST['cpf']);
    $phone = new Phone($_POST['ddd'], $_POST['phone']);
    $shippingAddress = new Address(
        $address->getdesaddress(),
        $address->getdesnumber(),
        $address->getdescomplement(),       
        $address->getdesdistrict(),
        $address->getdeszipcode(),
        $address->getdescity(),
        $address->getdesstate(),
        $address->getdescountry()
    );
    $birthDate = new DateTime($_POST['birth']);
    $sender = new Sender($order->getdesperson(), $cpf, $birthDate, $phone, $order->getdesemail(), $_POST['hash']);
    $holder = new Holder($order->getdesperson(), $cpf, $birthDate, $phone);
    $shipping = new Shipping($shippingAddress, (float)$cart->getvlfreight(), Shipping::PAC);
    $installment = new Installment((int)$_POST["installments_qtd"], (float)$_POST["installments_value"]);
    $billingAddress = new Address(
        $address->getdesaddress(),
        $address->getdesnumber(),
        $address->getdescomplement(),       
        $address->getdesdistrict(),
        $address->getdeszipcode(),
        $address->getdescity(),
        $address->getdesstate(),
        $address->getdescountry()
    );
    $creditCard = new CreditCard($_POST['token'], $installment, $holder, $billingAddress);
    $payment = new Payment($order->getidorder(), $sender, $shipping);
    foreach($cart->getProducts() as $product)
    {
        $item = new Item(
            (int)$product['idproduct'],
            $product['desproduct'],
            (float)$product['vlprice'],
            (int)$product['nrqtd']
        );
        $payment->addItem($item);
    }
    $payment->setCreditCard($creditCard);
    Transporter::sendTransaction($payment);
    
    echo json_encode([
        'success'=>true
    ]);
});

$app->post('/payment/boleto', function(){

    User::verifyLogin(false);

    $order = new Order();

    $order->getFromSession();

    $order->get((int)$order->getidorder());

    $address = $order->getAddress();

    $cart = $order->getCart();

    $cpf = new Document(Document::CPF, $_POST['cpf']);

    $phone = new Phone($_POST['ddd'], $_POST['phone']);

    $shippingAddress = new Address(
        $address->getdesaddress(),
        $address->getdesnumber(),
        $address->getdescomplement(),       
        $address->getdesdistrict(),
        $address->getdeszipcode(),
        $address->getdescity(),
        $address->getdesstate(),
        $address->getdescountry()
    );

    $birthDate = new DateTime($_POST['birth']);

    $sender = new Sender($order->getdesperson(), $cpf, $birthDate, $phone, $order->getdesemail(), $_POST['hash']);

    $shipping = new Shipping($shippingAddress, (float)$cart->getvlfreight(), Shipping::PAC);

    $payment = new Payment($order->getidorder(), $sender, $shipping);

    foreach($cart->getProducts() as $product)
    {
        $item = new Item(
            (int)$product['idproduct'],
            $product['desproduct'],
            (float)$product['vlprice'],
            (int)$product['nrqtd']
        );
        $payment->addItem($item);
    }
    
    $payment->setBoleto();

    Transporter::sendTransaction($payment);
    
    echo json_encode([
        'success'=>true
    ]);
});

$app->get('/payment', function(){

    User::verifyLogin(false);

    $order = new Order();

    $order->getFromSession();

    $years = [];

    for ($y = date('Y'); $y < date('Y')+14; $y++)
    {
        array_push($years, $y);
    }

    $page = new Page();

    $page->setTpl("payment", [
        "order"=>$order->getValues(),
        "msgError"=>Order::getError(),
        "years"=>$years,
        "pagseguro"=>[
            "urlJS"=>Config::getUrlJS(),
            "id"=>Transporter::createSession(),
            "maxInstallmentNoInterest"=>Config::MAX_INSTALLMENT_NO_INTEREST,
            "maxInstallment"=>Config::MAX_INSTALLMENT
        ]
    ]);
    
});


?>