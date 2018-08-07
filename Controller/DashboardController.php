<?php

namespace Tkuska\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Tkuska\DashboardBundle\WidgetProvider;
use Tkuska\DashboardBundle\Entity\Widget;

/**
 * Akcja controller.
 */
class DashboardController extends Controller
{
    /**
     * @Route("/dashboard/add_widget/{type}", options={"expose"=true}, name="add_widget")
     */ 
    public function addWidgetAction(WidgetProvider $provider, $type)
    {
        $widgetType = $provider->getWidgetType($type);

        $widget = new Widget();
        $widget->importConfig($widgetType);
        $widget->setUserId($this->getUser()->getId());

        $em = $this->getDoctrine()->getManager();
        $em->persist($widget);
        $em->flush();

        return $this->renderWidget($provider, $widget->getId());
    }

    /**
     * @Route("/dashboard/remove_widget/{id}", options={"expose"=true}, name="remove_widget")
     */
    public function removeWidgetAction($id)
    {
        $widget = $this->getDoctrine()->getRepository(Widget::class)->find($id);
        
        if ($widget) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($widget);
            $em->flush();
        }
        
        return new JsonResponse(true);
    }
    
    /**
     * @Route("/dashboard/update_widget/{id}/{x}/{y}/{width}/{height}", options={"expose"=true}, name="update_widget")
     */
    public function updateWidgetAction($id, $x, $y, $width, $height)
    {
        $widget = $this->getDoctrine()->getRepository(Widget::class)->find($id);

        if ($widget) {
            $widget
                ->setX($x)
                ->setY($y)
                ->setWidth($width)
                ->setHeight($height)
            ;

            $em = $this->getDoctrine()->getManager();
            $em->flush();
        }

        return new JsonResponse(true);
    }

    /**
     * @Route("/dashboard/update_title/{id}/{title}", options={"expose"=true}, name="update_title")
     */
    public function updateWidgetTitleAction($id, $title)
    {
        $widget = $this->getDoctrine()->getRepository(Widget::class)->find($id);

        if ($widget) {
            $widget->setTitle($title);
            $em = $this->getDoctrine()->getManager();
            $em->flush();
        }

        return new JsonResponse(true);
    }

    /**
     * @Route("/dashboard/render_widget/{id}", options={"expose"=true}, name="render_widget")
     */
    public function renderWidget(WidgetProvider $provider, $id)
    {
        $widget = $this->getDoctrine()->getRepository(Widget::class)->find($id);

        $response = new Response();
        $response->setContent("");
        
        if ($widget) {
            $widgetType = $provider->getWidgetType($widget->getType());

            if ($widgetType) {
                $widgetType->setParams($widget);
                $response->setContent($widgetType->render());
            }

        }
        return $response;
    }

    /**
     * @Route("/dashboard/widget_save_config/{id}", name="widget_save_config")
     */
    public function saveConfig(Request $request, WidgetProvider $provider, $id)
    {
        $config = $request->request->get("form")["json_form_".$id];
        $widget = $this->getDoctrine()->getRepository(Widget::class)->find($id);
        
        if ($widget) {
            $widget->setConfig($config);
            $em = $this->getDoctrine()->getManager();
            $em->flush();
        }

        return $this->redirectToRoute("homepage");
    }
    
    /**
     *
     * @Route("/", name="homepage")
     * @Method("GET")
     * @Template()
     */
    public function dashboardAction(WidgetProvider $provider)
    {
        $widgets = $provider->getMyWidgets();
        $widget_types = $provider->getWidgetTypes();
        return array('widgets' => $widgets, 'widget_types' => $widget_types);
    }
}
