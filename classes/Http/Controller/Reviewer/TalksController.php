<?php

declare(strict_types=1);

/**
 * Copyright (c) 2013-2017 OpenCFP
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/opencfp/opencfp
 */

namespace OpenCFP\Http\Controller\Reviewer;

use OpenCFP\Domain\Services\Authentication;
use OpenCFP\Domain\Services\Pagination;
use OpenCFP\Domain\Talk\TalkFilter;
use OpenCFP\Domain\Talk\TalkHandler;
use OpenCFP\Domain\ValidationException;
use OpenCFP\Http\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class TalksController extends BaseController
{
    public function indexAction(Request $request)
    {
        $reviewerId = $this->service(Authentication::class)->userId();

        $options = [
            'order_by' => $request->get('order_by'),
            'sort'     => $request->get('sort'),
        ];

        $formattedTalks = $this->service(TalkFilter::class)->getTalks(
            $reviewerId,
            $request->get('filter'),
            $options
        );

        $perPage    = (int) $request->get('per_page') ?: 20;
        $pagerfanta = new Pagination($formattedTalks, $perPage);
        $pagerfanta->setCurrentPage($request->get('page'));
        $pagination = $pagerfanta->createView('/reviewer/talks?', $request->query->all());

        $templateData = [
            'pagination'   => $pagination,
            'talks'        => $pagerfanta->getFanta(),
            'page'         => $pagerfanta->getCurrentPage(),
            'current_page' => $request->getRequestUri(),
            'totalRecords' => \count($formattedTalks),
            'filter'       => $request->get('filter'),
            'per_page'     => $perPage,
            'sort'         => $request->get('sort'),
            'order_by'     => $request->get('order_by'),
        ];

        return $this->render('reviewer/talks/index.twig', $templateData);
    }

    public function viewAction(Request $request)
    {
        /** @var TalkHandler $handler */
        $handler = $this->service(TalkHandler::class)
            ->grabTalk((int) $request->get('id'));

        if (!$handler->view()) {
            $this->service('session')->set('flash', [
                'type'  => 'error',
                'short' => 'Error',
                'ext'   => 'Could not find requested talk',
            ]);

            return $this->app->redirect($this->url('admin_talks'));
        }

        return $this->render('reviewer/talks/view.twig', ['talk' => $handler->getProfile()]);
    }

    public function rateAction(Request $request)
    {
        try {
            $this->validate([
                'rating' => 'required|integer',
            ]);

            return $this->service(TalkHandler::class)
                ->grabTalk((int) $request->get('id'))
                ->rate((int) $request->get('rating'));
        } catch (ValidationException $e) {
            return false;
        }
    }
}
