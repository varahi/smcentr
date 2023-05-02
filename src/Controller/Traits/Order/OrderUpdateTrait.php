<?php

declare(strict_types=1);

namespace App\Controller\Traits\Order;

use App\Entity\District;
use App\Entity\JobType;
use App\Entity\Order;
use App\Entity\Profession;
use App\Entity\City;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\JobTypeRepository;
use App\Repository\ProfessionRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
trait OrderUpdateTrait
{
    private $professionRepository;

    private $jobTypeRepository;

    private $cityRepository;

    private $districtRepository;

    public function __construct(
        ProfessionRepository $professionRepository,
        JobTypeRepository $jobTypeRepository,
        CityRepository $cityRepository,
        DistrictRepository $districtRepository
    ) {
        $this->professionRepository = $professionRepository;
        $this->jobTypeRepository = $jobTypeRepository;
        $this->cityRepository = $cityRepository;
        $this->districtRepository = $districtRepository;
    }

    public function updatePostData(
        Request $request,
        Order $order
    ) {
        $post = $request->request->get('order_form');
        $entityManager = $this->doctrine->getManager();

        if (isset($post['profession']) && $post['profession'] !=='') {
            $profession = $entityManager->getRepository(Profession::class)->findOneBy(['id' => $post['profession']]);
            if ($profession) {
                $order->setProfession($profession);
            }
        }
        if (isset($post['jobType']) && $post['jobType'] !=='') {
            $jobType = $entityManager->getRepository(JobType::class)->findOneBy(['id' => $post['jobType']]);
            if ($jobType) {
                $order->setJobType($jobType);
            }
        }
        if (isset($post['city']) && $post['city'] !=='') {
            $city = $entityManager->getRepository(City::class)->findOneBy(['id' => $post['city']]);
            if ($city) {
                $order->setCity($city);
            }
        }
        if (isset($post['district']) && $post['district'] !=='') {
            $district = $entityManager->getRepository(District::class)->findOneBy(['id' => $post['district']]);
            if ($district) {
                $order->setDistrict($district);
            }
        }
    }
}
