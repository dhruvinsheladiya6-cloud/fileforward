(function($) {

    "use strict";

    // Get Current Year
    document.querySelectorAll('[data-year]').forEach((el) => {
        el.textContent = new Date().getFullYear();
    });

    if (window.AOS) {
        AOS.init({ once: true, disable: 'mobile' });
    }

    if ($.LoadingOverlaySetup) {
        $.LoadingOverlaySetup({
            imageColor: getConfig.LoadingOverlayColor,
            size: 10,
            zIndex: 1100,
        });
    }

    let clipboardBtn = document.querySelector("#copy-btn");
    if (clipboardBtn) {
        let clipboard = new ClipboardJS(clipboardBtn);
        clipboard.on('success', function(e) {
            toastr.success(getConfig.copiedToClipboardSuccess);
        });
    }


    window.clipboardByClass = () => {
        let clipboardByClass = document.querySelectorAll(".copy");
        if (clipboardByClass) {
            clipboardByClass.forEach((el) => {
                let clipboard = new ClipboardJS(el);
                clipboard.on("success", () => {
                    toastr.success(getConfig.copiedToClipboardSuccess);
                });
            });
        }
    }

    window.clipboardByClass();

    let country = document.querySelector("#country"),
        mobile = $("#mobile"),
        mobileCode = document.querySelector("#mobile_code");
    if (country) {
        let countryOption = country.querySelector(`option[data-code="${getConfig.countryCode}"]`),
            mobileOption = mobileCode.querySelector(`option[data-code="${getConfig.countryCode}"]`);
        countryOption.selected = true;
        mobileOption.selected = true;
        country.addEventListener("change", () => {
            let mobileId = mobileCode.querySelector(`option[data-code="${country.options[country.selectedIndex].getAttribute("data-code")}"]`);
            mobileId.selected = true;
        });
        mobileCode.addEventListener("change", () => {
            let countryCode = country.querySelector(`option[data-code="${mobileCode.options[mobileCode.selectedIndex].getAttribute("data-code")}"]`);
            countryCode.selected = true;
        });
    }
    if (mobile.length) {
        mobile.on('propertychange input', function() {
            const input = $(this);
            input.val(input.val().replace(/[^\d]+/g, ''));
        });
    }

    // Dropdown
    var dropdown = document.querySelectorAll('[data-dropdown]');
    if (dropdown != null) {
        dropdown.forEach(function(el) {
            window.addEventListener('click', function(e) {
                if (el.contains(e.target)) {
                    el.classList.toggle('active');
                    setTimeout(function() {
                        el.classList.toggle('animated');
                    }, 0);
                } else {
                    el.classList.remove('active');
                    el.classList.remove('animated');
                }
            });
        });
    }

    // Navbar
    let navBar = document.querySelector('.nav-bar');
    if (navBar) {
        let navBarOP = () => {
            if (window.scrollY > 100) {
                navBar.classList.add('active');
            } else {
                navBar.classList.remove('active');
            }
        };
        window.addEventListener('load', navBarOP);
        window.addEventListener('scroll', navBarOP);
    }

    // NavbarMenu
    let navBarMenu = document.querySelector('.nav-bar-menu'),
        navBarIcon = document.querySelector('.nav-bar-menu-icon');
    let navBarCloseFunc = () => {
        navBarMenu.classList.remove('active');
        document.body.style.overflowY = 'auto';
    };
    if (navBarMenu) {
        navBarIcon.onclick = () => {
            navBarMenu.classList.add('active');
            document.body.style.overflowY = 'hidden';
        };
        navBarMenu.querySelector('.btn-close').onclick = () => {
            navBarCloseFunc();
        };
        navBarMenu.querySelector(".overlay").onclick = () => {
            navBarCloseFunc();
        };
        navBarMenu.querySelectorAll('.nav-bar-link').forEach((el) => {
            el.onclick = () => {
                navBarCloseFunc();
            };
        });
    }

    // Scroll To
    let links = document.querySelectorAll('[data-link]'),
        linkTop = document.querySelectorAll('[data-go-top]');
    if (links) {
        links.forEach((el) => {
            el.onclick = (e) => {
                e.preventDefault();
                let scrollTarget = document.querySelector(el.getAttribute('data-link')).offsetTop - 60;
                navBarCloseFunc();
                window.scrollTo('0', scrollTarget);
            };
        });
    }

    if (linkTop) {
        linkTop.forEach((el) => {
            el.onclick = (e) => {
                e.preventDefault();
                navBarCloseFunc();
                window.scrollTo('0', '0');
            };
        });
    }

    // Perfect Scrollbar
    const ps = document.querySelectorAll("[ps]");
    if (ps) {
        ps.forEach((el) => {
            new PerfectScrollbar(el);
            el.classList.remove("ps--active-x");
        });
    }

    // Download
    let fileboxDownload = $('.filebox-download'),
        downloadCounter = $(".download-counter");

    if (downloadCounter.length) {
        let counterNum = $(".counter-number");
        let counterFunc = setInterval(() => {
            counterNum.html(Number(counterNum.html()) - 1);
            if (counterNum.html() == 0) {
                clearInterval(counterFunc);
                appendDownloadBtn();
            }
        }, 1000);
    }

    let appendDownloadBtn = () => {
        let requestDownloadLink = getConfig.baseURL + '/' + downloadId + '/download/create';
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: requestDownloadLink,
            type: "POST",
            dataType: 'json',
            success: function(response) {
                if ($.isEmptyObject(response.error)) {
                    fileboxDownload.empty();
                    fileboxDownload.append('<a class="download-link" href="' + response.download_link + '">' + downloadBtnTxt + '</a>');
                    appenedDownloadingBtn();
                } else {
                    toastr.error(response.error);
                }
            }
        });
    }

    let appenedDownloadingBtn = () => {
        let downloadLink = $('.download-link');
        downloadLink.on('click', function(e) {
            e.preventDefault();
            fileboxDownload.empty();
            fileboxDownload.append('<button class="downloading-btn" disabled>' + downloadingBtnTxt + '</button>');
            location.href = $(this).attr('href');
            setTimeout(function() {
                fileboxDownload.empty();
                fileboxDownload.append('<button class="reDownload-btn" disabled>' + reDownloadBtnTxt + '&nbsp;<a id="reDownloadBtn" href="#">' + clickHereTxt + '<a></button>')
                reDownloadFn();
            }, 2000);
        });
    }

    let reDownloadFn = () => {
        let reDownloadBtn = $('#reDownloadBtn');
        reDownloadBtn.on('click', function(e) {
            e.preventDefault();
            appendDownloadBtn();
        });
    }


    if (typeof downloadWaitingTime != "undefined" && downloadWaitingTime == 0) {
        appenedDownloadingBtn();
    }

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[rel="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Plan Switcher
    let plans = document.querySelectorAll(".plans .plans-item"),
        planSwitcher = document.querySelector(".plan-switcher");
    if (planSwitcher) {
        planSwitcher.querySelectorAll(".plan-switcher-item").forEach((el, id) => {
            el.onclick = () => {
                planSwitcher.querySelectorAll(".plan-switcher-item").forEach((ele) => {
                    ele.classList.remove("active");
                });
                el.classList.add("active");
                plans.forEach((el) => {
                    el.classList.remove("active");
                });
                plans[id].classList.add("active");
            };
        });
    }

    window.passwordEye = () => {
        let password = document.querySelectorAll(".input-password");
        if (password) {
            password.forEach((el) => {
                let passwordBtn = el.querySelector("button"),
                    passwordInput = el.querySelector("input");
                passwordBtn.onclick = (e) => {
                    e.preventDefault();
                    if (passwordInput.type === "password") {
                        passwordInput.type = "text";
                        passwordBtn.innerHTML = `<i class="fas fa-eye-slash"></i>`;
                    } else {
                        passwordInput.type = "password";
                        passwordBtn.innerHTML = `<i class="fas fa-eye"></i>`;
                    }
                };
            });
        }
    }

    window.passwordEye();

    let confirmActionLink = $('.confirm-action');
    confirmActionLink.on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: getConfig.alertActionTitle,
            text: getConfig.alertActionText,
            icon: 'question',
            showCancelButton: true,
            allowOutsideClick: false,
            focusConfirm: false,
            confirmButtonText: getConfig.alertActionConfirmButton,
            confirmButtonColor: getConfig.primaryColor,
            cancelButtonText: getConfig.alertActionCancelButton,
        }).then((result) => {
            if (result.isConfirmed) {
                location.href = $(this).attr('href');
            }
        })
    });

    let confirmFormBtn = $('.confirm-action-form');
    confirmFormBtn.on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: getConfig.alertActionTitle,
            text: getConfig.alertActionText,
            icon: 'question',
            showCancelButton: true,
            allowOutsideClick: false,
            focusConfirm: false,
            confirmButtonText: getConfig.alertActionConfirmButton,
            confirmButtonColor: getConfig.primaryColor,
            cancelButtonText: getConfig.alertActionCancelButton,
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).parents('form')[0].submit();
            }
        })
    });

    let otpCode = $('#otp-code');
    otpCode.on('input', function() {
        this.value = this.value.replace(/\D/g, '');
    });

    let swiperVideos = document.querySelectorAll(".swiper-video"),
        swiperEle = document.querySelector(".swiper");
    if (swiperEle) {
        const swiper = new Swiper('.swiper', {
            autoplay: true,
            allowTouchMove: false,
            effect: 'fade',
            fadeEffect: {
                crossFade: false
            },
            on: {
                init: function() {
                    if (swiperVideos) {
                        if (this.slides[this.realIndex].classList.contains("swiper-video")) {
                            this.slides[this.realIndex].querySelector("video").play();
                        }
                    }
                },
                slideChange: function() {
                    if (swiperVideos) {
                        swiperVideos.forEach((el) => {
                            let video = el.querySelector("video");
                            video.load();
                        });
                    }
                },
                slideChangeTransitionStart: function() {
                    if (this.slides[this.realIndex].classList.contains("swiper-video")) {
                        this.slides[this.realIndex].querySelector("video").play();
                    }
                }
            }
        });
    }

})(jQuery);