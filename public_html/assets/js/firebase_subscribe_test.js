// firebase_subscribe.js

// браузер поддерживает уведомления
// вообще, эту проверку должна делать библиотека Firebase, но она этого не делает
if ('Notification' in window) {

    // пользователь уже разрешил получение уведомлений
    // подписываем на уведомления если ещё не подписали
    //if (Notification.permission === 'granted') {
        //subscribe();
    //}

    // по клику, запрашиваем у пользователя разрешение на уведомления
    // и подписываем его
    $('#subscribe').on('click', function () {
        //subscribe();

        // This function is only for test
        saveTokenToDatabase();
    });
}

function saveTokenToDatabase() {
    var url = 'http://smcentr.su.localhost/user-token'; // адрес скрипта на сервере который сохраняет ID устройства
    $.post(url, {
        //et5RE8KgsKs:APA91bHiqPo_5kgXYozxcuorulGxBzs9DGycPuXztAhcsE8mQrL41zAW9wPH3zLfo_Yy7FyYa9rIBiuiwTwlr7xX4GSeYet4bKq7Sb5ktLnK_Bo_aAvvjhZGlwrWVGwlBXYiqblRZoBC
        //d25NZDLqowo:APA91bHvEBrz-3ZgIvb8YazD6f3dpgQZdQ4be5i7P-XIQLNBIFtmhtHfxZkKeTIid-HsZ1KI6bmmp3MkNGD1hLzxDL23nsYejkjYks15q3Xj9emzAUND4kWgCaxPqgNgBYv83ML0y8U_
        token: 'eNUgeU2Sm8k:APA91bEf1Lt8IjNs4vC1K20QEoPvBz-2oO4i2vwIKzsRT4PaQiLDfK9AivrkVU8RaN2WtkP2BIgIWT1WUkPsSzI4KnTHEqPlMq8eA04MrWNGv39p9eH7mwN4b4EyeOE3BAgm7Lq-FquR'
    });
}