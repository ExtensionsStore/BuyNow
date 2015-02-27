/**
 * BuyNow js
 * 
 * @category   Aydus
 * @package    Aydus_BuyNow
 * @author     Aydus Consulting <davidt@aydus.com>
 */

var BuyNow = function ($)
{
    var loggedin;
    var addedtocart = false;
    var button;

    var options = {};
    var addToCartForm;

    var varienForm;
    var form;
    //inline style for progress
    var progressBackground;

    //not logged in button click
    var login = function ()
    {
        if (validateAddToCartForm()) {
        	
        	
            varienForm = buyNowLoginForm;
            form = varienForm.form;
            $('#buynow-login-button').click(loginSubmit);
            showPopup(true);
        }
    };
    
    var validateAddToCartForm = function()
    {
        if (addToCartForm.validator.validate()){
        	
        	return true;
        } 
        
        return false;
    };

    var register = function ()
    {
        window.location.href = options.registerUrl;
    }

    var loginSubmit = function (e)
    {
        e.preventDefault();

        if (varienForm.validator.validate()) {

            progress(true);
            var url = $(form).attr('action');

            var data = $(form).serialize();

            $.post(url, data, function (res) {

                if (!res.error) {

                    loginSuccess(res.data);

                } else {

                    $('#buynow-content .messages').show().find('span').text(res.data);

                }

            });

        }

    };

    var loginSuccess = function (data)
    {
        loggedin = true;
        updateCheckout(data);

        addToCart();
    };

    //on button click, add item to cart if NOT already in cart
    var addToCart = function ()
    {
        showPopup(true);
        progress(true);

        if (!addedtocart) {

            if (validateAddToCartForm()) {

                var $form = $('#product_addtocart_form');
                var data = $form.serialize();
                var url = options.addToCartUrl;

                $.post(url, data, function (res) {

                    progress(false);

                    if (!res.error) {

                        addedtocart = true;
                        updateCheckout(res.data);
                    	loadCheckout();
                        checkout();

                    } else {

                        $('#buynow-checkout-form .messages').show().find('span').text(res.data);
                    }

                });

            } else {

                showPopup(false);
            }

        } else {
        	
        	loadCheckout();
            checkout();
        }

    };

    var updateCheckout = function (data)
    {
        updateHeader(data.header);

        if (data.checkout) {
            $('#buynow-content').html(data.checkout);
        }

    };
    
    var loadCheckout = function()
    {
        $('#buynow-checkout-button').click(checkoutSubmit);
        
        var method = $('#ba_agreement_id').children(":selected").attr("method");
        $('#payment-method').val(method);

        $('#ba_agreement_id').change(function () {
            var method = $(this).children(":selected").attr("method");
            $('#payment-method').val(method);
        });

        $('#billing-address-select').change(changeBillingAddress);
        $('#shipping-address-select').change(updateShippingMethods);
        $('#shipping_method_select').change(changeShippingMethod);    	
    };

    var changeBillingAddress = function (e)
    {
        var billingAddressId = $(this).val();
        billingAddressId = parseInt(billingAddressId);
        if (!isNaN(billingAddressId) && billingAddressId > 0) {
            progress(true);
            var data = {billing_address_id: billingAddressId};
            var url = options.changeBillingAddressUrl;
            $.get(url, data, function (res) {
                progress(false);
                if (!res.error) {

                } else {
                    log(res.data);
                }
            });

        }
    }

    var updateShippingMethods = function (e)
    {
        var shippingAddressId = $(this).val();
        shippingAddressId = parseInt(shippingAddressId);
        if (!isNaN(shippingAddressId) && shippingAddressId > 0) {
            progress(true);
            var data = {shipping_address_id: shippingAddressId};
            var url = options.shippingMethodsUrl;
            $.get(url, data, function (res) {
                progress(false);
                if (!res.error) {
                    $('#shipping_method_select').replaceWith(res.data.html)
                    $('#shipping_method_select').change(changeShippingMethod);
                } else {
                    log(res.data);
                }
            });

        }
    };

    var changeShippingMethod = function (e)
    {
        var shippingMethod = $(this).val();

        if (shippingMethod.length > 0) {
            progress(true);
            var data = {shipping_method: shippingMethod};
            var url = options.changeShippingMethodUrl;
            $.get(url, data, function (res) {
                progress(false);
                if (!res.error) {

                } else {
                    log(res.data);
                }
            });

        }
    };

    //logged in, checkout button click
    var checkout = function ()
    {
        if (typeof buyNowCheckoutForm != 'undefined') {

            varienForm = buyNowCheckoutForm;
            form = varienForm.form;
            showPopup(true);
            progress(false);

        } else {

            window.location.href = options.onepageUrl;
        }
    };

    var checkoutSubmit = function (e)
    {
        e.preventDefault();
        if (varienForm.validator.validate()) {

            progress(true);
            var url = $(form).attr('action');

            var data = $(form).serialize();

            $.post(url, data, function (res) {

                progress(false);
                if (!res.error) {

                    updateHeader(res.data.header);
                    $('#buynow-content').html(res.data.success);

                } else {

                    $('#buynow-checkout-form .messages').show().find('span').text(res.data);
                }

            });

        }

    };

    //on button click, add to cart if not already in cart
    var buyNowButtonClick = function (e)
    {
    	e.preventDefault();
    	
        if (!loggedin) {
            login();
        } else {
            addToCart();
        }
    };

    //update the header
    var updateHeader = function (elements)
    {
        for (var selector in elements) {

            var element = elements[selector];

            if (element.html) {
                var html = element.html;
                if (element.parent) {
                    $(selector).parent().html(html);

                } else {
                    $(selector).html(html);
                }
            }
            if (element.attributes) {
                for (var attributeKey in element.attributes) {
                    var attributeValue = element.attributes[attributeKey];
                    $(selector).attr(attributeKey, attributeValue);
                }
            }
            if (element.hide) {
                $(selector).hide();
            }
            if (element.addClass && element.addClass.length > 0) {
                $(selector).addClass(element.addClass);
            }
            if (element.removeClass && element.removeClass.length > 0) {
                $(selector).removeClass(element.removeClass);
            }
            if (element.remove) {
                if (element.parent) {
                    $(selector).remove();

                } else {
                    $(selector).parent().remove();
                }
            }
        }
    };

    var showPopup = function (show)
    {
        if (show) {
            $('#buynow-popup').show();

        } else {
            $('#buynow-popup').hide();
        }
    };

    //progress background
    var progress = function (show, position)
    {
        var $container = $('#buynow-content');

        if (show) {

            $container.attr('style', progressBackground);

            if (position) {

                $container.css('background-position', position);
            }

            dim($container, true);

        } else {

            $container.attr('style', '');
            dim($container, false);

        }

    };

    //dim the container when in progress
    var dim = function ($container, dim)
    {
        $container.children().each(function () {

            if (dim) {
                $(this).css('opacity', '.3');
            } else {
                $(this).css('opacity', '1');
            }

        });

    };

    var log = function (message)
    {
        if (typeof console == 'object') {
            console.log(message);
        } else {
            alert(message);
        }

    };

    return {
        init: function ()
        {
            $(function () {

                if (buyNowOptions) {

                    loggedin = buyNowOptions.loggedIn;
                    options = buyNowOptions;

                    progressBackground = buyNowOptions.progressBackground;

                    //could be rwd popup form
                    addToCartForm = (productAddToCartForm.form.id == 'product_addtocart_form') ? productAddToCartForm : new VarienForm('product_addtocart_form');

                    var $buynowButton = $('#buynow-button');
                    $buynowButton.click(buyNowButtonClick);
                    button = $buynowButton[0];

                    $('.buynow-close').click(function (e) {
                        e.preventDefault();
                        $('#buynow-popup').hide();
                    });

                } else {
                    $('#buynow-button').hide();
                    log('buyNowOptions not set.');
                }

            });

        }

    };


};

if (!window.jQuery) {

    document.write('<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js">\x3C/script><script>jQuery.noConflict(); var buyNow = BuyNow(jQuery); buyNow.init();</script>');

} else {

    var buyNow = BuyNow(jQuery);
    buyNow.init();
}

