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
            <h1>Список заказов</h1>
        </div>
    </div>

<!-- <div class="row">
    <div class="col-md-12">
        <div class="filter_jobs">
             <button class="accordion">Фильтрация заказов</button>
<div class="panel">
  <div class="row">
        <div class="col-md-4">
            <div class="reg_contact">
                
                

                <select name="city" id="city_select">
    <option value="">Выберите город*</option>
    <option value="">Москва</option>
    <option value="">Санкт - Петербург</option>
    <option value="">Краснодар</option>
    <option value="">Саратов</option>
    <option value="">Хабаровск</option>
    <option value="">Владивосток</option>
</select>

<select name="raion" id="raion_select">
    <option value="">Выберите район*</option>
    <option value="">район</option>
    <option value="">район</option>
    <option value="">район</option>
    <option value="">район</option>
    <option value="">район</option>
    <option value="">район</option>
</select>

            </div>
        </div>

        <div class="col-md-8">
            <div class="reg_prof">
                <h3>Специализация</h3>
                <div class="cb_block">
                    <div class="cb_items">

<div class="main_checkbox">
<label class="checkbox">
    <input type="checkbox" checked>
    <span>Электрик</span>
</label>
</div>
 
<label class="checkbox">
    <input type="checkbox">
    <span>Монтаж розетки</span>
</label>

<label class="checkbox">
    <input type="checkbox">
    <span>Штроба</span>
</label>

<label class="checkbox">
    <input type="checkbox">
    <span>Установка счетчика</span>
</label>

<label class="checkbox">
    <input type="checkbox">
    <span>Замена проводки</span>
</label>

<label class="checkbox">
    <input type="checkbox">
    <span>Монтаж эл. щитка</span>
</label>
 

                    </div>

                    <div class="cb_items">

<div class="main_checkbox">
<label class="checkbox">
    <input type="checkbox" checked>
    <span>Сантехник</span>
</label>
</div>
 
<label class="checkbox">
    <input type="checkbox">
    <span>Замена труб</span>
</label>

<label class="checkbox">
    <input type="checkbox">
    <span>Устранение поломок</span>
</label>

<label class="checkbox">
    <input type="checkbox">
    <span>Установка крана</span>
</label>

<label class="checkbox">
    <input type="checkbox">
    <span>Замена крана</span>
</label>

<label class="checkbox">
    <input type="checkbox">
    <span>Замена планировки</span>
</label>
 

                    </div>

                    <div class="cb_items">

<div class="main_checkbox">
<label class="checkbox">
    <input type="checkbox" checked>
    <span>Монтажник</span>
</label>
</div>
 
<label class="checkbox">
    <input type="checkbox">
    <span>Монтаж труб</span>
</label>

<label class="checkbox">
    <input type="checkbox">
    <span>Монтаж проводки</span>
</label>

<label class="checkbox">
    <input type="checkbox">
    <span>Монтаж щитка</span>
</label>

<label class="checkbox">
    <input type="checkbox">
    <span>Монтаж эл. проводки</span>
</label>

<label class="checkbox">
    <input type="checkbox">
    <span>Монтаж ЛДСПР</span>
</label>
 

                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>
        </div>
    </div>
</div> -->

</div>    
</section>


<section id="list_items">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
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

                            <div class="status">
                                Ожидает
                            </div>
                        </div>
                    </div>
                    <div class="description">
                        Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа. 
                    </div>

                    <div class="btn_price">
                        <button>Взять заказ</button>
                        <span>1000 <i>руб.</i></span>
                    </div>

                    <div class="client_contact">
                        <div class="phone">
                            <img src="assets/images/phone.svg">
                            <div class="phone_num">+7 999 ...</div>
                        </div>

                        <div class="txt_warning">
                            Номер телефона клиента будет доступен только после получения заказа
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
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

                            <div class="status">
                                Ожидает
                            </div>
                        </div>
                    </div>
                    <div class="description">
                        Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа. 
                    </div>

                    <div class="btn_price">
                        <button>Взять заказ</button>
                        <span>1000 <i>руб.</i></span>
                    </div>

                    <div class="client_contact">
                        <div class="phone">
                            <img src="assets/images/phone.svg">
                            <div class="phone_num">+7 999 ...</div>
                        </div>

                        <div class="txt_warning">
                            Номер телефона клиента будет доступен только после получения заказа
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
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

                            <div class="status">
                                Ожидает
                            </div>
                        </div>
                    </div>
                    <div class="description">
                        Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа. 
                    </div>

                    <div class="btn_price">
                        <button>Взять заказ</button>
                        <span>1000 <i>руб.</i></span>
                    </div>

                    <div class="client_contact">
                        <div class="phone">
                            <img src="assets/images/phone.svg">
                            <div class="phone_num">+7 999 ...</div>
                        </div>

                        <div class="txt_warning">
                            Номер телефона клиента будет доступен только после получения заказа
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
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

                            <div class="status">
                                Ожидает
                            </div>
                        </div>
                    </div>
                    <div class="description">
                        Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа. 
                    </div>

                    <div class="btn_price">
                        <button>Взять заказ</button>
                        <span>1000 <i>руб.</i></span>
                    </div>

                    <div class="client_contact">
                        <div class="phone">
                            <img src="assets/images/phone.svg">
                            <div class="phone_num">+7 999 ...</div>
                        </div>

                        <div class="txt_warning">
                            Номер телефона клиента будет доступен только после получения заказа
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
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

                            <div class="status">
                                Ожидает
                            </div>
                        </div>
                    </div>
                    <div class="description">
                        Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа. 
                    </div>

                    <div class="btn_price">
                        <button>Взять заказ</button>
                        <span>1000 <i>руб.</i></span>
                    </div>

                    <div class="client_contact">
                        <div class="phone">
                            <img src="assets/images/phone.svg">
                            <div class="phone_num">+7 999 ...</div>
                        </div>

                        <div class="txt_warning">
                            Номер телефона клиента будет доступен только после получения заказа
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
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

                            <div class="status">
                                Ожидает
                            </div>
                        </div>
                    </div>
                    <div class="description">
                        Описание заказа. Описание заказа. Описание заказа. Описание заказа. Описание заказа. 
                    </div>

                    <div class="btn_price">
                        <button>Взять заказ</button>
                        <span>1000 <i>руб.</i></span>
                    </div>

                    <div class="client_contact">
                        <div class="phone">
                            <img src="assets/images/phone.svg">
                            <div class="phone_num">+7 999 ...</div>
                        </div>

                        <div class="txt_warning">
                            Номер телефона клиента будет доступен только после получения заказа
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<div class="col-md-12">
            <div class="reg_bottom">
                

                <button>Показать еще заказы</button>


               
            </div>
            
        </div>

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

</body>
</html>

