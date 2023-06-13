(function () {
    'use strict'
    window.onload = function() {


        const divCount = document.querySelector('.js-load-count');

        if (divCount != null) {
            fetch('/ticket-count-new')
                .then(response => response.json())
                .then(data => {

                    if (Number(data) > 0) {
                        divCount.querySelector('span').innerText = divCount.querySelector('span').innerText + ' (' + data + ')';
                    }
                })
                .catch(error => console.error(error));
        }

        const selectCity = document.querySelector('#Order_city');
        if(selectCity != null){
            selectCity.addEventListener('change',function(){
                let id = selectCity.value;
                let arrDistrict13 = [125, 126, 127, 128];
                document.querySelector('#Order_district-ts-control').click();

                const districtDiv = document.querySelector('#Order_district-ts-dropdown');

                const arrAllDistrict = districtDiv.querySelectorAll('.option');

                console.log(id);
                arrAllDistrict.forEach((option) => {
                    option.style.display = 'block';
                });
                arrAllDistrict.forEach((option) => {

                    if(id == 13){
                        if(arrDistrict13.includes(Number(option.dataset.value))){
                            option.style.display = 'block';
                        }else{
                            option.style.display = 'none';
                        }
                    }else{
                        if(arrDistrict13.includes(Number(option.dataset.value))){
                            option.style.display = 'none';
                        }else{
                            option.style.display = 'block';
                        }

                    }
                });
            });
        }



        const selectProfession = document.querySelector('#Order_profession');
        if(selectProfession != null){
            selectProfession.addEventListener('change',function(){
                let id = selectProfession.value;

                fetch('/api/job-types/profession-'+id)
                    .then(response => response.json())
                    .then(data => {
                        let arrJobType = data;
                        document.querySelector('#Order_jobType-ts-control').click();
                        const districtDiv = document.querySelector('#Order_jobType-ts-dropdown');

                        const arrAllDistrict = districtDiv.querySelectorAll('.option');

                        arrAllDistrict.forEach((option) => {
                            option.style.display = 'block';
                        });
                        arrAllDistrict.forEach((option) => {
                            if(arrJobType.includes(Number(option.dataset.value))){
                                option.style.display = 'block';
                            }else{
                                option.style.display = 'none';
                            }
                        });
                    })
                    .catch(error => console.error(error));
            });
        }

    }

})();
