    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no"/>
        <link href="assets/images/favicon.png" rel="icon" type="image/x-icon"/>
        <link href="assets/images/favicon.png" rel="shortcut icon" type="image/x-icon"/>

        <title>SMC - Выбор участия в системе</title>
        <meta name="description" content="">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="assets/css/bootstrap.min.css" >
        <!-- jQuery -->



        <link rel="stylesheet" href="assets/css/style.css"/>


        <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
        <link rel="stylesheet" href="assets/css/owl.theme.default.min.css">



    </head>


    <body>

    <header id="sticky_header">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <nav class="navbar navbar-expand-lg navbar-light">
                        <button class="navbar-toggler" type="button" data-toggle="offcanvas">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <a class="navbar-brand" href="#"><img src="assets/images/logo.svg"></a>
                        <div class="navbar-collapse offcanvas-collapse" id="navbarSupportedContent">
                            <button class="offcanvas-close" type="button" data-toggle="offcanvas-close">
                                ×
                            </button>
                            <ul class="navbar-nav mr-auto">
                                <li class="nav-item active">
                                    <a class="nav-link" href="#">Список заказов</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#">Техническая поддержка</a>
                                </li>

                                <li class="nav-item">

                                </li>

                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown"
                                       aria-haspopup="true" aria-expanded="false">
                                        <img src="assets/images/avatar.png">
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                        <a class="dropdown-item" href="#">Профиль</a>
                                        <a class="dropdown-item" href="#">Баланс</a>
                                        <a class="dropdown-item" href="#">История заказов</a>
                                        <a class="dropdown-item" href="#">Уведомления</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#">Выйти</a>
                                    </div>
                                </li>
                            </ul>

                        </div>
                    </nav>
                </div>
            </div>
        </div>

    </header>

    <section id="list_jobs">
        <div class="container">

            <div class="row">
                <div class="col-md-12">
                    <h1>Профиль</h1>
                </div>
            </div>



        </div>
    </section>


    <section id="profile_master">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <div class="profile_master">
                        <div class="profile_img">
                            <img src="assets/images/avatar.png">
                        </div>
                        <div class="profile_info">
                            <div class="fio">
                                Иванов Иван Иванович
                            </div>
                            <div class="proff">
                                электрик
                            </div>
                            <div class="city">
                                Москва
                            </div>
                        </div>
                    </div>

                    <div class="btn_profile_edit">
                        <button>Редактировать профиль</button>
                    </div>


                </div>

                <div class="col-md-4">
                    <div class="profile_balance">
                        <div class="balance">
                            <span>Баланс:</span>
                            <div>2300 руб.</div>
                        </div>
                        <button>Пополнить баланс</button>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section id="list_items">
        <div class="container">
            <div class="row">

                <div class="col-md-12">
                    <h3>Активные заявки</h3>
                </div>


                <div class="owl-carousel owl-theme" id="slider_active_order">

                    <div class="slide">
                        <div class="col-md-12">
                            <div class="job_item">
                                <div class="top_item">
                                    <div class="item_left">
                                        <h2>Ремонт сантехники</h2>
                                        <div class="city">
                                            Москва
                                        </div>
                                        <div class="raion">
                                            Красноселький район
                                        </div>
                                        <div class="date">03.06.2022</div>
                                    </div>
                                    <div class="item_right">
                                        <div class="id_item">
                                            ID 123456
                                        </div>

                                        <div class="prof">
                                            Сантехник
                                        </div>

                                        <div class="raiting">
                                            <p>Сложность</p>
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_empty.svg">
                                            <img src="assets/images/star_empty.svg">
                                        </div>

                                        <div class="status_performed">
                                            в работе
                                        </div>
                                    </div>
                                </div>
                                <div class="description">
                                    Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа.
                                </div>

                                <div class="btn_price">
                                    <button>Закрыть заявку</button>
                                    <span>1000 <i>руб.</i></span>
                                </div>

                                <div class="client_contact_visible">
                                    <div class="phone">
                                        <img src="assets/images/phone.svg">
                                        <div class="phone_num"><a href="tel:+79991112233">+7 999 111-22-33</a></div>
                                    </div>

                                    <div class="client_block">
                                        Алексей <img src="assets/images/avatar.png">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="slide">
                        <div class="col-md-12">
                            <div class="job_item">
                                <div class="top_item">
                                    <div class="item_left">
                                        <h2>Ремонт сантехники</h2>
                                        <div class="city">
                                            Москва
                                        </div>
                                        <div class="raion">
                                            Красноселький район
                                        </div>
                                        <div class="date">03.06.2022</div>
                                    </div>
                                    <div class="item_right">
                                        <div class="id_item">
                                            ID 123456
                                        </div>

                                        <div class="prof">
                                            Сантехник
                                        </div>

                                        <div class="raiting">
                                            <p>Сложность</p>
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_empty.svg">
                                            <img src="assets/images/star_empty.svg">
                                        </div>

                                        <div class="status_performed">
                                            в работе
                                        </div>
                                    </div>
                                </div>
                                <div class="description">
                                    Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа.
                                </div>

                                <div class="btn_price">
                                    <button>Закрыть заявку</button>
                                    <span>1000 <i>руб.</i></span>
                                </div>

                                <div class="client_contact_visible">
                                    <div class="phone">
                                        <img src="assets/images/phone.svg">
                                        <div class="phone_num"><a href="tel:+79991112233">+7 999 111-22-33</a></div>
                                    </div>

                                    <div class="client_block">
                                        Алексей <img src="assets/images/avatar.png">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="slide">
                        <div class="col-md-12">
                            <div class="job_item">
                                <div class="top_item">
                                    <div class="item_left">
                                        <h2>Ремонт сантехники</h2>
                                        <div class="city">
                                            Москва
                                        </div>
                                        <div class="raion">
                                            Красноселький район
                                        </div>
                                        <div class="date">03.06.2022</div>
                                    </div>
                                    <div class="item_right">
                                        <div class="id_item">
                                            ID 123456
                                        </div>

                                        <div class="prof">
                                            Сантехник
                                        </div>

                                        <div class="raiting">
                                            <p>Сложность</p>
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_empty.svg">
                                            <img src="assets/images/star_empty.svg">
                                        </div>

                                        <div class="status_performed">
                                            в работе
                                        </div>
                                    </div>
                                </div>
                                <div class="description">
                                    Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа.
                                </div>

                                <div class="btn_price">
                                    <button>Закрыть заявку</button>
                                    <span>1000 <i>руб.</i></span>
                                </div>

                                <div class="client_contact_visible">
                                    <div class="phone">
                                        <img src="assets/images/phone.svg">
                                        <div class="phone_num"><a href="tel:+79991112233">+7 999 111-22-33</a></div>
                                    </div>

                                    <div class="client_block">
                                        Алексей <img src="assets/images/avatar.png">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="slide">
                        <div class="col-md-12">
                            <div class="job_item">
                                <div class="top_item">
                                    <div class="item_left">
                                        <h2>Ремонт сантехники</h2>
                                        <div class="city">
                                            Москва
                                        </div>
                                        <div class="raion">
                                            Красноселький район
                                        </div>
                                        <div class="date">03.06.2022</div>
                                    </div>
                                    <div class="item_right">
                                        <div class="id_item">
                                            ID 123456
                                        </div>

                                        <div class="prof">
                                            Сантехник
                                        </div>

                                        <div class="raiting">
                                            <p>Сложность</p>
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_empty.svg">
                                            <img src="assets/images/star_empty.svg">
                                        </div>

                                        <div class="status_performed">
                                            в работе
                                        </div>
                                    </div>
                                </div>
                                <div class="description">
                                    Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа.
                                </div>

                                <div class="btn_price">
                                    <button>Закрыть заявку</button>
                                    <span>1000 <i>руб.</i></span>
                                </div>

                                <div class="client_contact_visible">
                                    <div class="phone">
                                        <img src="assets/images/phone.svg">
                                        <div class="phone_num"><a href="tel:+79991112233">+7 999 111-22-33</a></div>
                                    </div>

                                    <div class="client_block">
                                        Алексей <img src="assets/images/avatar.png">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-md-12">


                    <div class="btn_center">
                        <button>Показать все активные заявки</button>
                    </div>
                </div>

            </div>
        </div>
    </section>




    <section id="list_items">
        <div class="container">
            <div class="row">

                <div class="col-md-12">
                    <h3>Завершенные заявки</h3>
                </div>


                <div class="owl-carousel owl-theme" id="slider_complite_order">

                    <div class="slide">
                        <div class="col-md-12">
                            <div class="job_item">
                                <div class="top_item">
                                    <div class="item_left">
                                        <h2>Ремонт сантехники</h2>
                                        <div class="city">
                                            Москва
                                        </div>
                                        <div class="raion">
                                            Красноселький район
                                        </div>
                                        <div class="date">03.06.2022</div>
                                    </div>
                                    <div class="item_right">
                                        <div class="id_item">
                                            ID 123456
                                        </div>

                                        <div class="prof">
                                            Сантехник
                                        </div>

                                        <div class="raiting">
                                            <p>Сложность</p>
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_empty.svg">
                                            <img src="assets/images/star_empty.svg">
                                        </div>

                                        <div class="status_complite">
                                            завершен
                                        </div>
                                    </div>
                                </div>
                                <div class="description">
                                    Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа.
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="slide">
                        <div class="col-md-12">
                            <div class="job_item">
                                <div class="top_item">
                                    <div class="item_left">
                                        <h2>Ремонт сантехники</h2>
                                        <div class="city">
                                            Москва
                                        </div>
                                        <div class="raion">
                                            Красноселький район
                                        </div>
                                        <div class="date">03.06.2022</div>
                                    </div>
                                    <div class="item_right">
                                        <div class="id_item">
                                            ID 123456
                                        </div>

                                        <div class="prof">
                                            Сантехник
                                        </div>

                                        <div class="raiting">
                                            <p>Сложность</p>
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_empty.svg">
                                            <img src="assets/images/star_empty.svg">
                                        </div>

                                        <div class="status_complite">
                                            завершен
                                        </div>
                                    </div>
                                </div>
                                <div class="description">
                                    Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="slide">
                        <div class="col-md-12">
                            <div class="job_item">
                                <div class="top_item">
                                    <div class="item_left">
                                        <h2>Ремонт сантехники</h2>
                                        <div class="city">
                                            Москва
                                        </div>
                                        <div class="raion">
                                            Красноселький район
                                        </div>
                                        <div class="date">03.06.2022</div>
                                    </div>
                                    <div class="item_right">
                                        <div class="id_item">
                                            ID 123456
                                        </div>

                                        <div class="prof">
                                            Сантехник
                                        </div>

                                        <div class="raiting">
                                            <p>Сложность</p>
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_empty.svg">
                                            <img src="assets/images/star_empty.svg">
                                        </div>

                                        <div class="status_complite">
                                            завершен
                                        </div>
                                    </div>
                                </div>
                                <div class="description">
                                    Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="slide">
                        <div class="col-md-12">
                            <div class="job_item">
                                <div class="top_item">
                                    <div class="item_left">
                                        <h2>Ремонт сантехники</h2>
                                        <div class="city">
                                            Москва
                                        </div>
                                        <div class="raion">
                                            Красноселький район
                                        </div>
                                        <div class="date">03.06.2022</div>
                                    </div>
                                    <div class="item_right">
                                        <div class="id_item">
                                            ID 123456
                                        </div>

                                        <div class="prof">
                                            Сантехник
                                        </div>

                                        <div class="raiting">
                                            <p>Сложность</p>
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_full.svg">
                                            <img src="assets/images/star_empty.svg">
                                            <img src="assets/images/star_empty.svg">
                                        </div>

                                        <div class="status_complite">
                                            завершен
                                        </div>
                                    </div>
                                </div>
                                <div class="description">
                                    Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-md-12">


                    <div class="btn_center">
                        <button>Показать все завершенные заявки</button>
                    </div>
                </div>

            </div>
        </div>
    </section>



    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <ul>
                        <li><a href="">Способы оплаты</a></li>
                        <li><a href="">Политика конфиденциальности</a></li>
                    </ul>


                </div>

                <div class="col-md-4">

                </div>

                <div class="col-md-4">
                    <p>Центр поддержки клиентов: <a href="">8 800 100 00 00</a><br>
                        Звонок по России бесплатный</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

    <script src="assets/js/scripts.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

    <!-- Bootstrap JS + Popper JS -->
    <script defer src="assets/js/bootstrap.bundle.min.js"></script>


    <script type="text/javascript">
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
    </script>


    <script type="text/javascript">
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
    </script>


    </body>
    </html>
