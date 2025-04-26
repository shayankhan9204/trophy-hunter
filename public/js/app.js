/**
 * Theme: Crovex - Responsive Bootstrap 4 Admin Dashboard
 * Author: Mannatthemes
 * Module/App: Main Js
 */


(function ($) {

    'use strict';

    function initSlimscroll() {
        $('.slimscroll').slimscroll({
            height: 'auto',
            position: 'right',
            size: "7px",
            color: '#a2b1d021',
            opacity: 1,
            wheelStep: 5,
            touchScrollStep: 50
        });
    }


    function initMetisMenu() {
        //metis menu
        $(".metismenu").metisMenu();
        $(window).resize(function () {
            initEnlarge();
        });
    }

    function initLeftMenuCollapse() {
        // Left menu collapse
        $('.button-menu-mobile').on('click', function (event) {
            event.preventDefault();
            $("body").toggleClass("enlarge-menu");
            initSlimscroll();
        });
    }

    function initEnlarge() {
        if ($(window).width() < 1025) {
            $('body').addClass('enlarge-menu enlarge-menu-all');
        } else {
            // if ($('body').data('keep-enlarged') != true)
            $('body').removeClass('enlarge-menu enlarge-menu-all');
        }
    }

    function initTooltipPlugin() {
        $.fn.tooltip && $('[data-toggle="tooltip"]').tooltip()
    }

    function initMainIconTabMenu() {
        $('.main-icon-menu .nav-link').on('click', function (e) {
            $("body").removeClass("enlarge-menu");
            e.preventDefault();
            $(this).addClass('active');
            $(this).siblings().removeClass('active');
            $('.main-menu-inner').addClass('active');
            var targ = $(this).attr('href');
            $(targ).addClass('active');
            $(targ).siblings().removeClass('active');
        });
    }


    function initActiveMenu() {
        // === following js will activate the menu in left side bar based on url ====
        $(".leftbar-tab-menu a, .left-sidenav a").each(function () {
            var pageUrl = window.location.href.split(/[?#]/)[0];
            if (this.href == pageUrl) {
                $(this).addClass("active");
                $(this).parent().addClass("active"); // add active to li of the current link
                $(this).parent().parent().addClass("in");
                $(this).parent().parent().addClass("mm-show");
                $(this).parent().parent().parent().addClass("mm-active");
                $(this).parent().parent().prev().addClass("active"); // add active class to an anchor
                $(this).parent().parent().parent().addClass("active");
                $(this).parent().parent().parent().parent().addClass("mm-show"); // add active to li of the current link
                $(this).parent().parent().parent().parent().parent().addClass("mm-active");
                var menu = $(this).closest('.main-icon-menu-pane').attr('id');
                $("a[href='#" + menu + "']").addClass('active');

            }
        });
    }

    function initFeatherIcon() {
        feather.replace()
    }

    // Auto complate

    function initAutoComplate() {
        $(document).ready(function () {
            BindControls();
        });

        function BindControls() {
            var Countries = ['Forms',
                'Tables',
                'Charts',
                'Icones',
                'Maps'];

            $('#AllCompo').autocomplete({
                source: Countries,
                minLength: 0,
                scroll: true
            }).focus(function () {
                $(this).autocomplete("search", "");
            });
        }
    }


    function initMainIconMenu() {
        $(".navigation-menu a").each(function () {
            var pageUrl = window.location.href.split(/[?#]/)[0];
            if (this.href == pageUrl) {
                $(this).parent().addClass("active"); // add active to li of the current link
                $(this).parent().parent().parent().addClass("active"); // add active class to an anchor
                $(this).parent().parent().parent().parent().parent().addClass("active"); // add active class to an anchor
            }
        });
    }

    function initTopbarMenu() {
        $('.navbar-toggle').on('click', function (event) {
            $(this).toggleClass('open');
            $('#navigation').slideToggle(400);
        });

        $('.navigation-menu>li').slice(-2).addClass('last-elements');

        $('.navigation-menu li.has-submenu a[href="#"]').on('click', function (e) {
            if ($(window).width() < 992) {
                e.preventDefault();
                $(this).parent('li').toggleClass('open').find('.submenu:first').toggleClass('open');
            }
        });
    }

    function init() {
        initSlimscroll();
        initMetisMenu();
        initLeftMenuCollapse();
        initEnlarge();
        initTooltipPlugin();
        initMainIconTabMenu();
        initActiveMenu();
        initFeatherIcon();
        initAutoComplate();
        initMainIconMenu();
        initTopbarMenu();
        Waves.init();
    }

    init();

})(jQuery)


// Script by Moiz

$(document).ready(function () {
    $("#customer_parent").on("change", function () {
        if ($(this).val() === "new_customer") {
            $("#customermodal").modal("show");

        }
    });

    $(document).on("change", ".new_student", function () {
        if ($(this).val() === "new_student") {
            $("#studentmodal").modal("show");
        }
    });
});

$(document).ready(function () {

    $('#studentbody').on('click', '.remove', function () {
        $(this).closest('tr').remove();
    });
});

$(document).ready(function () {

    $('#subjectBody').on('click', '.remove', function () {
        $(this).closest('tr').remove();
    });
});


$.validator.addMethod(
    "pattern",
    function (value, element, param) {
        if (this.optional(element)) {
            return true;
        }
        const regex = new RegExp(param);
        return regex.test(value);
    },
    "Invalid format."
);

$.validator.addMethod(
    "validatePhone",
    function (value, element) {
        return /^\+60[0-9]{9}$/.test(value);
    },
    "Phone number must start with +60 and be exactly 12 digits long."
);

$.validator.addMethod(
    "validateWhatsApp",
    function (value, element) {
        return /^\+60[0-9]{9}$/.test(value);
    },
    "WhatsApp number must start with +60 and be exactly 12 digits long."
);

$.validator.addMethod(
    "validateNumber",
    function (value, element) {
        return /^\+60[0-9]{9}$/.test(value);
    },
    "Number must start with +60 and be exactly 12 digits long."
);


$.validator.addMethod(
    "validateAgeFromNRIC",
    function (value, element) {
        if (this.optional(element)) {
            return true;
        }

        const nricDate = value.substring(0, 6);
        const year = parseInt(nricDate.substring(0, 2), 10);
        const month = parseInt(nricDate.substring(2, 4), 10);
        const day = parseInt(nricDate.substring(4, 6), 10);

        const currentYear = new Date().getFullYear();
        const fullYear = year + (year > currentYear % 100 ? 1900 : 2000);

        if (fullYear < currentYear - 50 || fullYear > currentYear - 20) {
            $(element).data(
                "customMessage",
                "Your year of birth indicates you are either under 20 or over 50 years old."
            );
            return false;
        }

        if (month < 1 || month > 12) {
            $(element).data(
                "customMessage",
                "The month in your NRIC must be between 01 and 12."
            );
            return false;
        }

        const daysInMonth = new Date(fullYear, month, 0).getDate();
        if (day < 1 || day > daysInMonth) {
            $(element).data(
                "customMessage",
                `The day in your NRIC must be between 01 and ${daysInMonth} for the given month and year.`
            );
            return false;
        }

        const today = new Date();
        let age = today.getFullYear() - fullYear;
        if (today.getMonth() < month - 1 || (today.getMonth() === month - 1 && today.getDate() < day)) {
            age--;
        }

        if (age < 20 || age > 50) {
            $(element).data(
                "customMessage",
                "Your age must be between 20 and 50 years based on NRIC."
            );
            return false;
        }

        return true;
    },
    function (params, element) {
        return $(element).data("customMessage") || "Your NRIC contains invalid date details.";
    }
);

$(document).ready(function () {
    $.validator.addMethod(
        "notZero",
        function (value, element) {
            return parseFloat(value) > 0; // Ensure the value is greater than 0
        },
        "Value cannot be 0 or 0.00."
    );

    $(".make-staff-payment-form").validate({
        rules: {
            staff_id: {
                required: true,
            },
            payment_date: {
                required: true,
            },
            salary_month: {
                required: true,
            },
            salary_year: {
                required: true,
            },
            basic_salary: {
                required: true,
            },
            overtime_pay: {
                required: true,
            },
            bonus: {
                required: true,
            },
            commission: {
                required: true,
            },
            working_days_eligible: {
                required: true,
            },
            food_allowance: {
                required: true,
            },
            additional_allowance: {
                required: true,
            },
            unpaid_leave_days: {
                required: true,
            },
            claim_amount: {
                required: true,
            },
            employee_epf: {
                required: true,
            },
            employee_socso: {
                required: true,
            },
            employee_eis: {
                required: true,
            },
            employee_pcb: {
                required: true,
            },
            employer_epf: {
                required: true,
            },
            employer_socso: {
                required: true,
            },
            employer_eis: {
                required: true,
            },
            employer_hrdf: {
                required: true,
            },
            paying_account: {
                required: true,
            },
        },
        messages: {
            staff_id: "Please select a staff member.",
            payment_date: "Please provide the payment date.",
            salary_month: "Please select the salary month.",
            salary_year: "Please provide the salary year.",
            basic_salary: {
                required: "Please provide the basic salary.",
            },
            overtime_pay: {
                required: "Please provide the overtime pay.",
            },
            bonus: {
                required: "Please provide the bonus amount.",
            },
            commission: {
                required: "Please provide the commission amount.",
            },
            working_days_eligible: {
                required: "Please provide the number of working days eligible.",
            },
            food_allowance: {
                required: "Please provide the food allowance.",
            },
            additional_allowance: {
                required: "Please provide the additional allowance.",
            },
            unpaid_leave_days: {
                required: "Please provide the number of unpaid leave days.",
            },
            claim_amount: {
                required: "Please provide the claim amount.",
            },
            employee_epf: {
                required: "Please provide the employee EPF amount.",
            },
            employee_socso: {
                required: "Please provide the employee SOCSO amount.",
            },
            employee_eis: {
                required: "Please provide the employee EIS amount.",
            },
            employee_pcb: {
                required: "Please provide the employee PCB amount.",
            },
            employer_epf: {
                required: "Please provide the employer EPF amount.",
            },
            employer_socso: {
                required: "Please provide the employer SOCSO amount.",
            },
            employer_eis: {
                required: "Please provide the employer EIS amount.",
            },
            employer_hrdf: {
                required: "Please provide the employer HRDF amount.",
            },
            paying_account: {
                required: "Please specify the paying account.",
            },
        },
        errorElement: "span",
        errorPlacement: function (error, element) {
            element.closest(".form-group").append(error);
        },
        submitHandler: function (form) {
            var $submitButton = $(form).find('button[type="submit"]');
            $submitButton.prop("disabled", true).text("Submitting...");
            form.submit();
        },
    });

    $(".make-staff-payment-form").on("submit", function (e) {
        if (!$(this).valid()) {
            e.preventDefault();
        }
    });
});

$(document).ready(function () {
    const toggleButton = $(".button-menu-mobile");
    const toggleIcon = toggleButton.find("i");
    const sidebar = $(".left-sidenav");
    const sidebarMenu = $(".metismenu.left-sidenav-menu");

    toggleButton.on("click", function () {
        if (window.innerWidth <= 768) {
            sidebar.toggleClass("mm-active");

            sidebarMenu.toggleClass("mm-show");

            if (sidebar.hasClass("mm-active")) {
                sidebar.css("transform", "translateX(0)");
                toggleButton.addClass("active");
                toggleIcon.removeClass("la la-bars").addClass("ti-close");
            } else {
                sidebar.css("transform", "translateX(-100%)");
                toggleButton.removeClass("active");
                toggleIcon.removeClass("ti-close").addClass("la la-bars");
            }
        }
    });

    $(window).on("resize", function () {
        if (window.innerWidth > 768) {
            sidebar.css("transform", "").removeClass("mm-active");
            sidebarMenu.removeClass("mm-show");
            toggleButton.removeClass("active");
            toggleIcon.removeClass("ti-close").addClass("la la-bars");
        }
    });

    if (window.innerWidth <= 768) {
        sidebar.css("transform", "translateX(-100%)");
        sidebar.removeClass("mm-active");
        sidebarMenu.removeClass("mm-show");
        toggleButton.removeClass("active");
        toggleIcon.removeClass("ti-close").addClass("la la-bars");
    }
});

$(document).ready(function () {
    if ($(window).width() <= 768) {
        $(".page-title-box .float-right a").html('<i class="ti-plus"></i>');
    }
    $('.settings-side-bar-ul li a, .table-card .table thead tr th span, .table-card .table thead tr th .btn').each(function () {
        let text = $(this).text();
        let formattedText = text.replace(/([a-z])([A-Z])/g, '$1 $2');
        $(this).text(formattedText);
    });
});

$(document).ready(function () {
    $('.settings-side-bar-ul li a').on('click', function (e) {
        e.preventDefault();

        $('.sifu-cform').animate({scrollTop: 0}, 'slow', function () {
            console.log('Scrolled to the top of .sifu-cform');
        });
    });
});

$(document).ready(function () {
    $('[name="time"]').bootstrapMaterialDatePicker({
        date: false,
        shortTime: false,
        format: 'HH:mm',
        clearButton: true
    });
});

$(document).ready(function () {
    $('form').attr('autocomplete', 'off');
});

$(document).ready(function () {
    $('#togglePassword').on('click', function () {
        const passwordField = $('#respassword');
        const icon = $(this).find('i');
        const fieldType = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', fieldType);
        icon.toggleClass('fa-eye fa-eye-slash');
    });
});

$(document).ready(function () {
    $('.users-change-pass').hide();
    $('#resetpass-btn').on('click', function () {
        $('.users-reset-pass').hide();
        $('.users-change-pass').show();
    });
    $('#backpwd-btn').on('click', function () {
        $('.users-reset-pass').show();
        $('.users-change-pass').hide();
    });
});

$("#download_pdf").on("click", function () {
    const cardElement = $(".download_salarypdf .card")[0]; // Get the DOM element
    const {jsPDF} = window.jspdf;

    // Button reference
    const button = $(this);

    // Disable the button and change text
    button.prop("disabled", true).text("Downloading...");

    // Generate the PDF
    html2canvas(cardElement, {
        scale: 1.5, // Slightly lower scale for smaller size
        useCORS: true, // Enable cross-origin support
    }).then(function (canvas) {
        const imgData = canvas.toDataURL("image/jpeg", 0.8); // Convert to JPEG and reduce quality
        const pdf = new jsPDF("p", "mm", "a4");

        // Calculate dimensions to fit A4 size
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

        // Add the image to the PDF
        pdf.addImage(imgData, "JPEG", 0, 0, pdfWidth, pdfHeight);

        // Save the PDF
        pdf.save("Salary_Slip.pdf");

        // Change button text back and re-enable the button
        button.prop("disabled", false).text("Download PDF");
    }).catch(function (error) {
        console.error("Error generating PDF:", error);
        button.prop("disabled", false).text("Download PDF");
    });
});

$("#download_spdf").on("click", function () {
    const cardElement = $(".download_slippdf .card")[0]; // Get the DOM element
    const {jsPDF} = window.jspdf;

    // Get the h1 text inside .invoice-head
    let invoiceTitle = $(".download_slippdf .invoice-head h1").text().trim();
    invoiceTitle = invoiceTitle.replace(/\s+/g, "-").toLowerCase(); // Replace spaces with "-" and convert to lowercase
    const pdfFileName = invoiceTitle ? `${invoiceTitle}.pdf` : "invoice.pdf";

    // Button reference
    const button = $(this);

    // Disable the button and change text
    button.prop("disabled", true).text("Downloading...");

    // Generate the PDF
    html2canvas(cardElement, {
        scale: 1.5, // Slightly lower scale for smaller size
        useCORS: true, // Enable cross-origin support
    }).then(function (canvas) {
        const imgData = canvas.toDataURL("image/jpeg", 0.8); // Convert to JPEG and reduce quality
        const pdf = new jsPDF("p", "mm", "a4");

        // Calculate dimensions to fit A4 size
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

        // Add the image to the PDF
        pdf.addImage(imgData, "JPEG", 0, 0, pdfWidth, pdfHeight);

        // Save the PDF with the derived name
        pdf.save(pdfFileName);

        // Change button text back and re-enable the button
        button.prop("disabled", false).text("Download PDF");
    }).catch(function (error) {
        console.error("Error generating PDF:", error);
        button.prop("disabled", false).text("Download PDF");
    });
});
