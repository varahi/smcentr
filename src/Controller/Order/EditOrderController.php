<?php

namespace App\Controller\Order;

use App\Controller\Traits\Order\OrderUpdateTrait;
use App\Entity\Order;
use App\Form\Order\OrderFormCompanyType;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\JobTypeRepository;
use App\Repository\ProfessionRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class EditOrderController extends AbstractController
{
    use OrderUpdateTrait;

    public const ROLE_COMPANY = 'ROLE_COMPANY';

    /**
     * @param Security $security
     * @param Environment $twig
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        Security $security,
        Environment $twig,
        ManagerRegistry $doctrine,
        int $projectId
    ) {
        $this->security = $security;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->projectId = $projectId;
    }

    /**
     *
     * @IsGranted("ROLE_COMPANY")
     * @Route("/edit-order/order-{id}", name="app_edit_order")
     */
    public function editOrder(
        Request $request,
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Order $order,
        CityRepository $cityRepository,
        DistrictRepository $districtRepository,
        JobTypeRepository $jobTypeRepository,
        ProfessionRepository $professionRepository
    ): Response {
        if (!$this->security->isGranted(self::ROLE_COMPANY)) {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        $cities = $cityRepository->findAllOrder(['name' => 'ASC']);
        $districts = $districtRepository->findAllOrder(['name' => 'ASC']);
        $professions = $professionRepository->findAllOrder(['name' => 'ASC']);
        $jobTypes = $jobTypeRepository->findAllOrder(['name' => 'ASC']);

        $user = $this->security->getUser();
        if ($this->isGranted(self::ROLE_COMPANY)) {
            $form = $this->createForm(OrderFormCompanyType::class, $order, [
                'userId' => $user->getId(),
                'level' => $order->getLevel()
            ]);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted()) {

            // Update data
            $this->updatePostData($request, $order);
            $order->setUsers($user);
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($order);
            $entityManager->flush();

            // Redirect
            $message = $translator->trans('Order updated', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute("app_company_profile");
        }

        return $this->render('order/edit.html.twig', [
            'user' => $user,
            'order' => $order,
            'cities' => $cities,
            'districts' => $districts,
            'professions' => $professions,
            'jobTypes' => $jobTypes,
            'orderForm' => $form->createView()
        ]);
    }

    /**
     *
     * @IsGranted("ROLE_COMPANY")
     * @Route("/delete-order/order-{id}", name="app_delete_order")
     */
    public function deleteOrder(
        TranslatorInterface $translator,
        NotifierInterface $notifier,
        Order $order
    ) {
        if (!$this->security->isGranted(self::ROLE_COMPANY)) {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        $user = $this->security->getUser();
        if ($order->getUsers()->getId() !== $user->getId()) {
            $message = $translator->trans('Please login', array(), 'flash');
            $notifier->send(new Notification($message, ['browser']));
            return $this->redirectToRoute('app_login');
        }

        $order->setUsers(null);
        $order->setPerformer(null);
        $entityManager = $this->doctrine->getManager();
        $entityManager->flush();

        //$orderTaxRate = $order->getCustomTaxRate();
        //$tax = $order->getPrice() * $company->getServiceTaxRate();

        $entityManager->remove($order);
        $entityManager->flush();

        $message = $translator->trans('Order removed', array(), 'flash');
        $notifier->send(new Notification($message, ['browser']));
        return $this->redirectToRoute('app_company_profile');
    }
}
