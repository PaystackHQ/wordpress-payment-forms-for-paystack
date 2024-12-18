function PffPaystackFee()
{

    this.DEFAULT_PERCENTAGE = 0.015;
    this.DEFAULT_ADDITIONAL_CHARGE = 10000;
    this.DEFAULT_THRESHOLD = 250000;
    this.DEFAULT_CAP = 200000;

    this.__initialize = function () {

        this.percentage = this.DEFAULT_PERCENTAGE;
        this.additional_charge = this.DEFAULT_ADDITIONAL_CHARGE;
        this.threshold = this.DEFAULT_THRESHOLD;
        this.cap = this.DEFAULT_CAP;

        if (window && window.KKD_PAYSTACK_CHARGE_SETTINGS) {
            this.percentage = window.KKD_PAYSTACK_CHARGE_SETTINGS.percentage;
            this.additional_charge = window.KKD_PAYSTACK_CHARGE_SETTINGS.additional_charge;
            this.threshold = window.KKD_PAYSTACK_CHARGE_SETTINGS.threshold;
            this.cap = window.KKD_PAYSTACK_CHARGE_SETTINGS.cap;
        }

    }

    this.chargeDivider = 0;
    this.crossover = 0;
    this.flatlinePlusCharge = 0;
    this.flatline = 0;

    this.withPercentage = function (percentage) {
        this.percentage = percentage;
        this.__setup();
    };

    this.withAdditionalCharge = function (additional_charge) {
        this.additional_charge = additional_charge;
        this.__setup();
    };

    this.withThreshold = function (threshold) {
        this.threshold = threshold;
        this.__setup();
    };

    this.withCap = function (cap) {
        this.cap = cap;
        this.__setup();
    };

    this.__setup = function () {
        this.__initialize();
        this.chargeDivider = this.__chargeDivider();
        this.crossover = this.__crossover();
        this.flatlinePlusCharge = this.__flatlinePlusCharge();
        this.flatline = this.__flatline();
    };

    this.__chargeDivider = function () {
        return 1 - this.percentage;
    };

    this.__crossover = function () {
        return this.threshold * this.chargeDivider - this.additional_charge;
    };

    this.__flatlinePlusCharge = function () {
        return (this.cap - this.additional_charge) / this.percentage;
    };

    this.__flatline = function () {
        return this.flatlinePlusCharge - this.cap;
    };

    this.addFor = function (amountinkobo) {
        if (amountinkobo > this.flatline) {
            return parseInt(Math.round(amountinkobo + this.cap));
        } else if (amountinkobo > this.crossover) {
            return parseInt(
                Math.round((amountinkobo + this.additional_charge) / this.chargeDivider)
            );
        } else {
            return parseInt(Math.round(amountinkobo / this.chargeDivider));
        }
    };

    this.__setup = function () {
        this.chargeDivider = this.__chargeDivider();
        this.crossover = this.__crossover();
        this.flatlinePlusCharge = this.__flatlinePlusCharge();
        this.flatline = this.__flatline();
    };

    this.__setup();
}

(function ($) {
    "use strict";
    $(document).ready(
        function ($) {

            /*if ( 0 < $(".date-picker").length ) {
				$(".date-picker").each( function() {
					$(".date-picker").datepicker(
						{
							dateFormat: "mm/dd/yy",
							prevText: '<i class="fa fa-caret-left"></i>',
							nextText: '<i class="fa fa-caret-right"></i>'
						}
					);
				} );
			}*/

            if ( $("#pf-vamount").length ) {
                  var amountField = $("#pf-vamount");
                  calculateTotal();
            } else {
                var amountField = $("#pf-amount");
            }
            var max = 10;
            amountField.keydown(
                function (e) {
                    format_validate(max, e);
                }
            );

            amountField.keyup(
                function (e) {
                    checkMinimumVal();
                }
            );

            $.fn.digits = function () {
                return this.each(
                    function () {
                        $(this).text(
                            $(this)
                            .text()
                            .replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,")
                        );
                    }
                );
            };

            calculateFees();

            $(".pf-number").keydown(
                function (event) {
                    if (event.keyCode == 46 
                        || event.keyCode == 8 
                        || event.keyCode == 9 
                        || event.keyCode == 27 
                        || event.keyCode == 13 
                        || (event.keyCode == 65 && event.ctrlKey === true) 
                        || (event.keyCode >= 35 && event.keyCode <= 39)
                    ) {
                        return;
                    } else {
                        if (event.shiftKey 
                            || ((event.keyCode < 48 || event.keyCode > 57) 
                            && (event.keyCode < 96 || event.keyCode > 105))
                        ) {
                            event.preventDefault();
                        }
                    }
                }
            );
            if ($("#pf-quantity").length) {
                calculateTotal();
            };

            $("#pf-quantity, #pf-vamount, #pf-amount").on(
                "change", function () {
                    calculateTotal();
                    calculateFees();
                }
            );

            $(".paystack-form").on(
                "submit", function (e) {
                    var requiredFieldIsInvalid = false;
                    e.preventDefault();

                    $("#pf-agreementicon").removeClass("rerror");

                    $(this)
                    .find("input, select, textarea")
                    .each(
                        function () {
                            $(this).removeClass("rerror"); //.css({ "border-color":"#d1d1d1" });
                        }
                    );
                    var email = $(this)
                    .find("#pf-email")
                    .val();
					
                    var amount;
                    if ($("#pf-vamount").length) {
                        amount = $("#pf-vamount").val();
                        calculateTotal();
                    } else {
                        amount = $(this)
                        .find("#pf-amount")
                        .val();
                    }
					

                    if (Number(amount) > 0) {
                    } else {
                        $(this)
                        .find("#pf-amount,#pf-vamount")
                        .addClass("rerror"); //  css({ "border-color":"red" });
                        $("html,body").animate(
                            { scrollTop: $(".rerror").offset().top - 110 },
                            500
                        );
                        return false;
                    }
                    if (!validateEmail(email)) {
                        $(this)
                        .find("#pf-email")
                        .addClass("rerror"); //.css({ "border-color":"red" });
                        $("html,body").animate(
                            { scrollTop: $(".rerror").offset().top - 110 },
                            500
                        );
                        return false;
                    }
                    if (checkMinimumVal() == false) {
                        $(this)
                        .find("#pf-amount")
                        .addClass("rerror"); //.css({ "border-color":"red" });
                        $("html,body").animate(
                            { scrollTop: $(".rerror").offset().top - 110 },
                            500
                        );
                        return false;
                    }

                    $(this)
                    .find("input, select, text, textarea")
                    .filter("[required]")
                    .filter(
                        function () {
                            return this.value === "";
                        }
                    )
                    .each(
                        function () {
                            $(this).addClass("rerror");
                            requiredFieldIsInvalid = true;
                        }
                    );

                    if ($("#pf-agreement").length && !$("#pf-agreement").is(":checked")) {
                        $("#pf-agreementicon").addClass("rerror");
                        requiredFieldIsInvalid = true;
                    }

                    if (requiredFieldIsInvalid) {
                        $("html,body").animate(
                            { scrollTop: $(".rerror").offset().top - 110 },
                            500
                        );
                        return false;
                    }

                    var self = $(this);
                    var $form = $(this);

                    $.blockUI({ message: "Please wait..." });

                    var formdata = new FormData(this);

                    $.ajax(
                        {
                            url: $form.attr("action"),
                            type: "POST",
                            data: formdata,
                            mimeTypes: "multipart/form-data",
                            contentType: false,
                            cache: false,
                            processData: false,
                            dataType: "JSON",
                            success: function (data) {
                                $.unblockUI();

                                data.custom_fields.push(
                                    {
                                        "display_name": "Plugin",
                                        "variable_name": "plugin",
                                        "value": "pff-paystack"
                                    }
                                )
                                if ( data.result == "success" ) {
                                    var names = data.name.split(" ");
                                    var firstName = names[0] || "";
                                    var lastName = names[1] || "";
                                    var quantity = data.quantity;

									$("#pf-nonce").val(data.invoiceNonce);
                                    
                                    if (data.plan == "none" || data.plan == "" || data.plan == "no") {
                                        var handler = PaystackPop.setup(
                                            {
                                                key: pffSettings.key,
                                                email: data.email,
                                                amount: data.total,
                                                firstname: firstName,
                                                lastname: lastName,
                                                currency: data.currency,
                                                subaccount: data.subaccount,
                                                bearer: data.txnbearer,
                                                transaction_charge: data.transaction_charge,
                                                ref: data.code,
                                                metadata: { custom_fields: data.custom_fields },
                                                callback: function (response) {
                                                    $.blockUI({ message: "Please wait..." });
                                                    $.post(
                                                        $form.attr("action"),
                                                        {
															action: "pff_paystack_confirm_payment",
															code: response.trxref,
															quantity: quantity,
															nonce: data.confirmNonce
														},
                                                        function (newdata) {
															data = JSON.parse(newdata);
															if (data.result == "success2") {
																window.location.href = data.link;
															}
															if (data.result == "success") {
																$(".paystack-form")[0].reset();
																$("html,body").animate(
																	{ scrollTop: $(".paystack-form").offset().top - 110 },
																	500
																);

																self.before('<div class="alert-success">' + data.message + '</div>');
																$(this)
																	.find("input, select, textarea")
																.each(
																	function () {
																		$(this).css(
																			{
																				"border-color": "#d1d1d1",
																				"background-color": "#fff"
																			}
																		);
																	}
																);
																calculateFees();

																$.unblockUI();
															} else {
																self.before('<div class="alert-danger">' + data.message + '</div>');
																$.unblockUI();
															}
														}
													);
                                                },
                                                onClose: function () { }
                                              }
                                        );
                                    } else {
                                        var handler = PaystackPop.setup(
                                            {
                                                key: pffSettings.key,
                                                email: data.email,
                                                plan: data.plan,
                                                firstname: firstName,
                                                lastname: lastName,
                                                ref: data.code,
                                                currency: data.currency,
                                                subaccount: data.subaccount,
                                                bearer: data.txnbearer,
                                                transaction_charge: data.transaction_charge,
                                                metadata: { custom_fields: data.custom_fields },
                                                callback: function (response) {
                                                    $.blockUI({ message: "Please wait..." });
                                                    $.post(
                                                        $form.attr("action"),
                                                        {
                                                            action: "pff_paystack_confirm_payment",
                                                            code: response.trxref,
															nonce: data.confirmNonce
                                                          },
                                                        function (newdata) {
                                                            data = JSON.parse(newdata);
                                                            if (data.result == "success2") {
                                                                window.location.href = data.link;
                                                            }
                                                            if (data.result == "success") {
                                                                    $(".paystack-form")[0].reset();
                                                                    $("html,body").animate(
                                                                        { scrollTop: $(".paystack-form").offset().top - 110 },
                                                                        500
                                                                    );

                                                                  self.before('<div class="alert-success">' + data.message + '</div>');
                                                                  $(this)
                                                                  .find("input, select, textarea")
                                                                .each(
                                                                    function () {
                                                                        $(this).css(
                                                                            {
                                                                                "border-color": "#d1d1d1",
                                                                                "background-color": "#fff"
                                                                            }
                                                                        );
                                                                    }
                                                                );
																calculateFees();

                                                            	$.unblockUI();
                                                            } else {
                                                                self.before('<div class="alert-danger">' + data.message + '</div>');
                                                                $.unblockUI();
                                                            }
                                                        }
                                                    );
                                                },
                                                onClose: function () { }
                                            }
                                        );
                                    }

                                    handler.openIframe();
                                } else {
                                    alert(data.error_message);
                                }

                            },
                            error: function (xhr, status, error) {
                                console.log("An error occurred");
                                console.log("XHR: ", xhr);
                                console.log("Status: ", status);
                                console.log("Error: ", error);
                            }
                        }
                    );
                }
            );

            $(".retry-form").on(
                "submit", function (e) {
                    var self = $(this);
                    var $form = $(this);
                    e.preventDefault();

                    $.blockUI({ message: "Please wait..." });

                    var formdata = new FormData(this);

                    $.ajax(
                        {
                            url: $form.attr("action"),
                            type: "POST",
                            data: formdata,
                            mimeTypes: "multipart/form-data",
                            contentType: false,
                            cache: false,
                            processData: false,
                            dataType: "JSON",
                            success: function (data) {
                                data.custom_fields.push(
                                    {
                                        "display_name": "Plugin",
                                        "variable_name": "plugin",
                                        "value": "pff-paystack"
                                    }
                                )
                                $.unblockUI();
                                if (data.result == "success") {
                                    var names = data.name.split(" ");
                                    var firstName = names[0] || "";
                                    var lastName = names[1] || "";
                                    var quantity = data.quantity;
                                    
									$("#pf-nonce").val(data.retryNonce);

                                    if (data.plan == "none" || data.plan == "" || data.plan == "no") {
                                        var handler = PaystackPop.setup(
                                            {
                                                key: pffSettings.key,
                                                email: data.email,
                                                amount: data.total,
                                                firstname: firstName,
                                                lastname: lastName,
                                                ref: data.code,
                                                currency: data.currency,
                                                subaccount: data.subaccount,
                                                bearer: data.txnbearer,
                                                transaction_charge: data.transaction_charge,
                                                metadata: { custom_fields: data.custom_fields },
                                                callback: function (response) {
                                                    $.blockUI({ message: "Please wait..." });
                                                    $.post(
                                                        $form.attr("action"),
                                                        {
                                                            action: "pff_paystack_confirm_payment",
                                                            code: response.trxref,
                                                            quantity: quantity,
															retry: true,
															nonce: data.confirmNonce
                                                        },
                                                        function (newdata) {
                                                            data = JSON.parse(newdata);
                                                            if (data.result == "success2") {
                                                                window.location.href = data.link;
                                                            }
                                                            if (data.result == "success") {
																// Get the current URL
																const currentUrl = window.location.href;

																// Use URL object to parse the current URL
																const url = new URL( currentUrl );

																// Redirect to the same URL without query parameters
																window.location.href = url.origin + url.pathname;
                                                            } else {
                                                                self.before('<div class="alert-success">' + data.message + '</div>');
                                                                $.unblockUI();
                                                            }
                                                        }
                                                    );
                                                },
                                                onClose: function () { }
                                            }
                                        );
                                    } else {
                                        var handler = PaystackPop.setup(
                                            {
                                                key: pffSettings.key,
                                                email: data.email,
                                                plan: data.plan,
                                                firstname: firstName,
                                                lastname: lastName,
                                                ref: data.code,
                                                currency: data.currency,
                                                subaccount: data.subaccount,
                                                bearer: data.txnbearer,
                                                transaction_charge: data.transaction_charge,
                                                metadata: { custom_fields: data.custom_fields },
                                                callback: function (response) {
                                                    $.blockUI({ message: "Please wait..." });
                                                    $.post(
                                                        $form.attr("action"),
                                                        {
                                                            action: "pff_paystack_confirm_payment",
                                                            code: response.trxref,
															retry: true,
															nonce: data.confirmNonce
                                                        },
                                                        function (newdata) {
                                                            data = JSON.parse(newdata);
                                                            if (data.result == "success2") {
                                                                window.location.href = data.link;
                                                            }
                                                            if (data.result == "success") {
																// Get the current URL
																const currentUrl = window.location.href;

																// Use URL object to parse the current URL
																const url = new URL(currentUrl);

																// Redirect to the same URL without query parameters
																window.location.href = url.origin + url.pathname;
                                                            } else {
                                                                self.before('<div class="alert-danger">' + data.message + '</div>');
                                                                $.unblockUI();
                                                            }
                                                        }
                                                    );
                                                },
                                                onClose: function () { }
                                            }
                                        );
                                    }

                                    handler.openIframe();
                                } else {
                                    alert(data.message);
                                }
                            },
                            error: function (xhr, status, error) {
                                console.log("An error occurred");
                                console.log("XHR: ", xhr);
                                console.log("Status: ", status);
                                console.log("Error: ", error);
                            }
                        }
                    );
                }
            );


			function checkMinimumVal() {
				if ($("#pf-minimum-hidden").length) {
					var min_amount = Number($("#pf-minimum-hidden").val());
					var amt = Number($("#pf-amount").val());
					if (min_amount > 0 && amt < min_amount) {
						$("#pf-min-val-warn").text( "Amount cannot be less than the minimum amount");
						return false;
					} else {
						$("#pf-min-val-warn").text("");
						$("#pf-amount").removeClass("rerror");
					}
				}
			}
			
			function format_validate(max, e) {
				var value = amountField.text();
				if (e.which != 8 && value.length > max) {
					e.preventDefault();
				}
				// Allow: backspace, delete, tab, escape, enter and .
				if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 
					// Allow: Ctrl+A
					|| (e.keyCode == 65 && e.ctrlKey === true) 
					// Allow: Ctrl+C
					|| (e.keyCode == 67 && e.ctrlKey === true) 
					// Allow: Ctrl+X
					|| (e.keyCode == 88 && e.ctrlKey === true) 
					// Allow: home, end, left, right
					|| (e.keyCode >= 35 && e.keyCode <= 39)
				) {
					// let it happen, don't do anything
					calculateFees();
					return;
				}
				// Ensure that it is a number and stop the keypress
				if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) 
					&& (e.keyCode < 96 || e.keyCode > 105)
				) {
					e.preventDefault();
				} else {
					calculateFees();
				}
			}
			
			function calculateTotal() {
				var unit;

				if ($("#pf-vamount").length) {
					unit = $("#pf-vamount").val();
				} else {
					unit = $("#pf-amount").val();
				}
				var quant = $("#pf-quantity").val();

				var newvalue = unit * quant;
			
				if (quant == "" || quant == null) {
					quant = 1;
				} else {
					$("#pf-total").val(newvalue);
				}
			
			}
			function calculateFees(transaction_amount) {
				setTimeout(
					function () {
						transaction_amount = transaction_amount || parseInt(amountField.val());
						var currency = $("#pf-currency").val();
						var quant = $("#pf-quantity").val();
						if ($("#pf-vamount").length) {
							var name = $("#pf-vamount option:selected").attr("data-name");
							$("#pf-vname").val(name);
						}
						if (transaction_amount == "" 
							|| transaction_amount == 0 
							|| transaction_amount.length == 0 
							|| transaction_amount == null 
							|| isNaN(transaction_amount)
						) {
								  var total = 0;
								  var fees = 0;
						} else {
							var obj = new PffPaystackFee();
			
							obj.withAdditionalCharge(pffSettings.fee.adc);
							obj.withThreshold(pffSettings.fee.ths);
							obj.withCap(pffSettings.fee.cap);
							obj.withPercentage(pffSettings.fee.prc);
							if (quant) {
									transaction_amount = transaction_amount * quant;
							}
							var total = obj.addFor(transaction_amount * 100) / 100;
							var fees = total - transaction_amount;
						}
						$(".pf-txncharge")
						.hide()
						.html(currency + " " + fees.toFixed(2))
						.show()
						.digits();
						$(".pf-txntotal")
						.hide()
						.html(currency + " " + total.toFixed(2))
						.show()
						.digits();
					}, 100
				);
			}
			
			function validateEmail(email) {
				var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
				return re.test(email);
			}

        }
    );
})(jQuery);