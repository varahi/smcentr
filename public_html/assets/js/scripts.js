
    $(function () {
      'use strict';

      $('[data-toggle="offcanvas"]').on('click', function () {
        $('.offcanvas-collapse').addClass('open');
        $('body').addClass('offcanvas-open');
      })
      $('[data-toggle="offcanvas-close"]').on('click', function () {
        $('.offcanvas-collapse').removeClass('open');
        $('body').removeClass('offcanvas-open');

      })
    })


// sticky
window.onscroll = function() {myFunction()};
var header = document.getElementById("sticky_header");
var sticky = header.offsetTop;

function myFunction() {
  if (window.pageYOffset > sticky) {
    header.classList.add("sticky");
  } else {
    header.classList.remove("sticky");
  }
} 

// accordion

var acc = document.getElementsByClassName("accordion");
var i;

for (i = 0; i < acc.length; i++) {
    acc[i].addEventListener("click", function() {
        /* Toggle between adding and removing the "active" class,
        to highlight the button that controls the panel */
        this.classList.toggle("active");

        /* Toggle between hiding and showing the active panel */
        var panel = this.nextElementSibling;
        if (panel.style.display === "block") {
            panel.style.display = "none";
        } else {
            panel.style.display = "block";
        }
    });
}

    $(document).ready(function(){
        const slider = $("#slider_active_order").owlCarousel({
            loop:true,
            margin:10,
            nav:true,
            dots: false,
            items:4,
            responsive:{
                0:{
                    items:1
                },
                600:{
                    items:2
                },
                1000:{
                    items:3
                }
            }
        });
    });

    $(document).ready(function(){
        const slider = $("#slider_complite_order").owlCarousel({
            loop:true,
            margin:10,
            nav:true,
            dots: false,
            items:4,
            responsive:{
                0:{
                    items:1
                },
                600:{
                    items:2
                },
                1000:{
                    items:3
                }
            }
        });
    });