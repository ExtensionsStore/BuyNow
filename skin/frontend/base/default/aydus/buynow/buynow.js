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
    var loginLabel;
    var checkoutLabel;
    var progressBackground;
    var addToCartUrl;

    var button;
    var dialog;
    var varienForm;
    var form;

    var options = {
        autoOpen: false,
        height: 'auto',
        width: 350,
        modal: true,
        fluid: true,
        buttons: {
            Cancel: function () {
                dialog.dialog("close");
            }
        },
        close: function () {
        },
        resize: function () {
            fluidDialog();
        }
    };

    var login = function ()
    {
        varienForm = buyNowLoginForm;
        form = varienForm.form;
        options.buttons[loginLabel] = loginSubmit;
        dialog = $("#buynow-login").dialog(options);
        dialog.dialog("open");
    };

    var loginSubmit = function ()
    {
        if (varienForm.validator.validate()) {

            progress('#buynow-login .content', true);
            var url = $(form).attr('action');

            var data = $(form).serialize();

            $.post(url, data, function (res) {

                if (!res.error) {

                    loginSuccess(res.data);
                    progress('#buynow-login .content', false);

                } else {

                    $('#buynow-login-form .messages').show().find('span').text(res.data);
                }

            });

        }

    };

    var loginSuccess = function (data)
    {
        $('#buynow-checkout').html(data.checkout);
        loggedin = true;
        updateHeader(data.header);

        dialog.dialog("close");
        delete options.buttons[loginLabel];
        checkout();
    };

    var checkout = function ()
    {
        varienForm = buyNowCheckoutForm;
        form = varienForm.form;

        checkoutLabel = $('#buynow-checkout-button').attr('title');
        options.buttons[checkoutLabel] = checkoutSubmit;
        dialog = $("#buynow-checkout").dialog(options);
        dialog.dialog("open");
    };

    var checkoutSubmit = function ()
    {
        if (varienForm.validator.validate()) {

            progress('#buynow-checkout .content', true);
            var url = $(form).attr('action');

            var data = $(form).serialize();

            $.post(url, data, function (res) {

                if (!res.error) {

                    checkoutSuccess(res.data);
                    progress('#buynow-checkout .content', false);

                } else {

                    $('#buynow-checkout-form .messages').show().find('span').text(res.data);
                }

            });

        }

    };

    var checkoutSuccess = function (data)
    {

    };

    var addToCart = function (callback)
    {
        if (productAddToCartForm.validator.validate()){
            
            var form = productAddToCartForm.form;
            var data = $(form).serialize();

            $.post(addToCartUrl, data, function (res) {
                if (!res.error) {

                    $('#buynow-checkout').html(res.data.checkout);
                    updateHeader(res.data.header);  
                    
                    callback();

                } else {

                    $('#buynow-checkout-form .messages').show().find('span').text(res.data);
                }

            });            
        }

    };

    var buyNowButtonClick = function (e)
    {
        if (!loggedin) {
            addToCart(login);
        } else {
            addToCart(checkout);
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

    //progress background
    var progress = function (selector, show, position)
    {
        var $container = $(selector);
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

    var fluidDialog = function ()
    {
        var $visible = $(".ui-dialog:visible");
        // each open dialog
        $visible.each(function () {
            var $this = $(this);
            var dialog = $this.find(".ui-dialog-content").data("ui-dialog");
            // if fluid option == true
            if (dialog.options.fluid) {
                var wWidth = $(window).width();
                // check window width against dialog width
                if (wWidth < (parseInt(dialog.options.maxWidth) + 50)) {
                    // keep dialog from filling entire screen
                    $this.css("max-width", "90%");
                } else {
                    // fix maxWidth bug
                    $this.css("max-width", dialog.options.maxWidth + "px");
                }
                //reposition dialog
                dialog.option("position", dialog.options.position);
            }
        });

    };

    return {
        init: function ()
        {
            $(function () {

                if (buyNowOptions) {

                    loggedin = buyNowOptions.loggedIn;

                    checkoutLabel = buyNowOptions.checkoutLabel;
                    loginLabel = buyNowOptions.loginLabel;
                    progressBackground = buyNowOptions.progressBackground;
                    addToCartUrl = buyNowOptions.addToCartUrl;

                    // on window resize run function
                    $(window).resize(function () {
                        fluidDialog();
                    });

                    // catch dialog if opened within a viewport smaller than the dialog width
                    $(document).on("dialogopen", ".ui-dialog", function (event, ui) {
                        fluidDialog();
                    });

                    var $buynowButton = $('#buynow-button');
                    $buynowButton.click(buyNowButtonClick);
                    button = $buynowButton[0];
                }

            });

        }

    };


};

if (!window.jQuery) {

    document.write('<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js">\x3C/script><script>jQuery.noConflict(); var buyNow = BuyNow(jQuery); buyNow.init();</script>');
    document.write('<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css" />');
    document.write('<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js">\x3C/script>');

} else {

    var buyNow = BuyNow(jQuery);
    buyNow.init();

    if (!jQuery.ui) {
        document.write('<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css" />');
        document.write('<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js">\x3C/script>');
    }
}

