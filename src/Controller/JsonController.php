<?php

namespace App\Controller;

use App\Entity\Profession;
use App\Repository\JobTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class JsonController extends AbstractController
{
    public function __construct(
        JobTypeRepository $jobTypeRepository
    ) {
        $this->jobTypeRepository = $jobTypeRepository;
    }

    /**
     * @Route("/api/job-types/profession-{id}", name="api_job_types")
     * @return Response
     */
    public function apiProfession(
        Profession $profession
    ) {
        $items = $this->jobTypeRepository->findByProfession($profession);
        $arrData = $this->getJsonArrData($items);

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->setContent(json_encode($arrData));

        return $response;
    }

    private function getJsonArrData($items)
    {
        if (!$items) {
            return null;
        }

        foreach ($items as $item) {
            if ($item->getId()) {
                $itemId = $item->getId();
            }
            $arrData[] = [
                'id' => $itemId,
            ];
        }
        return $arrData;
    }
}
