"use strict";
// preloder
$(function () {
    $(document).ready(function () {
        setTimeout(function () {
            $("#container").addClass("loaded");

            if ($("#container").hasClass("loaded")) {
                $("#preloader")
                    .delay(1000)
                    .queue(function () {
                        $(this).remove();
                    });
            }
        }, 1500);
    });
});

const topbar_offer_hidden_id = document.getElementById(
    "topbar_offer_hidden_id"
);

if (topbar_offer_hidden_id) {
    const endDate = topbar_offer_hidden_id.value;

    CountDown(endDate);
}

function CountDown(lastDate) {
    const selectDay = document.getElementById("day");
    const selectHour = document.getElementById("hour");
    const selectMinute = document.getElementById("minute");
    const selectSecound = document.getElementById("second");

    const selectDayOffer = document.getElementById("dayOffer");
    const selectHourOffer = document.getElementById("hourOffer");
    const selectMinuteOffer = document.getElementById("minuteOffer");
    const selectSecoundOffer = document.getElementById("secondOffer");

    if (selectDay && selectHour && selectMinute && selectSecound) {
        let showDate = "";
        let showHour = "";
        let showMinute = "";
        let showSecound = "";

        const provideDate = new Date(lastDate);

        // Date components

        const _seconds = 1000;
        const _minutes = _seconds * 60;
        const _hours = _minutes * 60;
        const _date = _hours * 24;

        const timer = setInterval(() => {
            const now = new Date();
            const distance = provideDate.getTime() - now.getTime();

            if (distance < 0) {
                clearInterval(timer);
                return;
            }

            showDate = Math.floor(distance / _date);
            showHour = Math.floor((distance % _date) / _hours);
            showMinute = Math.floor((distance % _hours) / _minutes);
            showSecound = Math.floor((distance % _minutes) / _seconds);

            selectDay.innerHTML =
                showDate < 10
                    ? `0${showDate} <span>d</span>`
                    : `${showDate} <span>d</span>`;
            selectHour.innerHTML =
                showHour < 10
                    ? `0${showHour} <span>h</span>`
                    : `${showHour} <span>h</span>`;
            selectMinute.innerHTML =
                showMinute < 10
                    ? `0${showMinute} <span>m</span>`
                    : `${showMinute} <span>m</span>`;
            selectSecound.innerHTML =
                showSecound < 10
                    ? `0${showSecound} <span>s</span>`
                    : `${showSecound} <span>s</span>`;

            if (
                selectDayOffer &&
                selectHourOffer &&
                selectMinuteOffer &&
                selectSecoundOffer
            ) {
                selectDayOffer.innerHTML =
                    showDate < 10 ? `0${showDate} ` : showDate;
                selectHourOffer.innerHTML =
                    showHour < 10 ? `0${showHour} ` : showHour;
                selectMinuteOffer.innerHTML =
                    showMinute < 10 ? `0${showMinute}` : showMinute;
                selectSecoundOffer.innerHTML =
                    showSecound < 10 ? `0${showSecound}` : showSecound;
            }
        }, 1000);
    }
}

// nav_stcky
$(window).scroll(function () {
    var scrolling = $(this).scrollTop();

    if (scrolling > 50) {
        $(".menu_bg").addClass("nav-bg");
    } else {
        $(".menu_bg").removeClass("nav-bg");
    }
});

// mobile nav
const openBtn = document.querySelector("#nav-opn-btn");
const closeBtn = document.querySelector("#nav-cls-btn");
const offcanvasContainer = document.querySelector("#offcanvas-nav");

function openNav() {
    document.body.style.overflowY = "hidden";
    offcanvasContainer.classList.add("open");
}

function closeNav() {
    document.body.style.overflowY = "";
    offcanvasContainer.classList.remove("open");
}

openBtn.addEventListener("click", openNav);
closeBtn.addEventListener("click", closeNav);

//informetion_sarch_link active js
$(".informetion_sarch_link li a ").on("click", function () {
    $(".informetion_sarch_link li a ").removeClass("active");
    $(this).addClass("active");
});

//pagination active js
$(".pagination li a ").on("click", function () {
    $(".pagination li a ").removeClass("active");
    $(this).addClass("active");
});

//  AOS js
$(window).on("scroll", function () {
    AOS.init();
});

$(function () {
    $(".back_to_top").on("click", function (e) {
        e.preventDefault();
        $("html, body").animate(
            {
                scrollTop: 0,
            },
            "smooth"
        );
    });

    // banner-slick
    $(".banner_slick").slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        dots: true,
        autoplay: true,
        autoplaySpeed: 1500,
        speed: 3000,
        fade: true,
    });

    // categories_slick
    $(".categories_slick").slick({
        slidesToShow: 6,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true,
        autoplaySpeed: 1500,
        speed: 3000,
        responsive: [
            {
                breakpoint: 1025,
                settings: {
                    slidesToShow: 4,
                    slidesToScroll: 1,
                },
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1,
                },
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 2,
                },
            },
        ],
    });

    $('.slickSlider').each(function(){
        const that = $(this);

        $(this).slick({
            slidesToShow: parseInt(that.data('slidestoshow') || 3),
            slidesToScroll: parseInt(that.data('slidestoscroll') || 1),
            autoplay: true,
            autoplaySpeed: 1500,
            speed: 3000,
            nextArrow: ".slick_arrow_right",
            prevArrow: ".slick_arrow_left",

            responsive: [
                {
                    breakpoint: 1399,
                    settings: {
                        slidesToShow: parseInt(that.data('desktopitem') || 2),
                    },
                },
                {
                    breakpoint: 1199,
                    settings: {
                        slidesToShow: parseInt(that.data('laptop') || 2),
                    },
                },
                {
                    breakpoint: 991,
                    settings: {
                        slidesToShow: parseInt(that.data('tablet') || 2),
                    },
                },
                {
                    breakpoint: 767,
                    settings: {
                        slidesToShow: parseInt(that.data('mobile') || 2),
                    },
                },
            ],
        });
    });

    // discount_slick
    $(".discount_slick").slick({
        slidesToShow: 3,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 1500,
        speed: 3000,
        nextArrow: ".slick_arrow_right",
        prevArrow: ".slick_arrow_left",

        responsive: [
            {
                breakpoint: 1399,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 1199,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 991,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 1,
                },
            },
        ],
    });

    // deals_slick
    $(".deals_slick").slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true,
        speed: 3000,
        autoplaySpeed: 1500,
        responsive: [
            {
                breakpoint: 1399,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 1199,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 991,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 2,
                },
            },
        ],
    });

    // discount_slick_two
    $(".discount_slick_two").slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 1500,
        speed: 3000,
        nextArrow: ".slick_arrow_right",
        prevArrow: ".slick_arrow_left",
        responsive: [
            {
                breakpoint: 1399,
                settings: {
                    slidesToShow: 3,
                },
            },
            {
                breakpoint: 1199,
                settings: {
                    slidesToShow: 3,
                },
            },
            {
                breakpoint: 991,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 767,
                settings: {
                    slidesToShow: 1,
                },
            },
        ],
    });

    // deals_slick
    $(".sign_up_left_slider_main").slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true,
        autoplaySpeed: 1500,
        speed: 1500,
        dots: true,
        fade: true,
    });
});

function typeWriter(
    selector_target,
    text_list,
    placeholder = false,
    i = 0,
    text_list_i = 0,
    delay_ms = 130
) {
    if (!i) {
        if (placeholder) {
            Array.from(document.querySelectorAll(selector_target)).forEach(
                (element) => (element.placeholder = "")
            );
        } else {
            Array.from(document.querySelectorAll(selector_target)).forEach(
                (element) => (element.innerHTML = "")
            );
        }
    }
    let txt = text_list[text_list_i];
    if (i < txt.length) {
        if (placeholder) {
            Array.from(document.querySelectorAll(selector_target)).forEach(
                (element) => (element.placeholder += txt.charAt(i))
            );
        } else {
            Array.from(document.querySelectorAll(selector_target)).forEach(
                (element) => (element.innerHTML += txt.charAt(i))
            );
        }
        i++;
        setTimeout(
            typeWriter,
            delay_ms,
            selector_target,
            text_list,
            placeholder,
            i,
            text_list_i
        );
    } else {
        text_list_i++;
        if (typeof text_list[text_list_i] === "undefined") {
            setTimeout(
                typeWriter,
                delay_ms * 5,
                selector_target,
                text_list,
                placeholder
            );
        } else {
            i = 0;
            setTimeout(
                typeWriter,
                delay_ms * 3,
                selector_target,
                text_list,
                placeholder,
                i,
                text_list_i
            );
        }
    }
}

let searchInput = document.getElementById("search_input");

if (searchInput) {
    let placeholderText = searchInput.placeholder;

    let text_list = [placeholderText];

    typeWriter("#search_input", text_list, true);
}
