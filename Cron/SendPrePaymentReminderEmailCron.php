<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Cron;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Mollie\Payment\Config as MollieConfig;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Service\Email\SendPrepaymentReminderEmail;
use Mollie\Subscriptions\Service\Mollie\CheckIfSubscriptionIsActive;

class SendPrePaymentReminderEmailCron
{
    /**
     * @var MollieConfig
     */
    private $mollieConfig;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;

    /**
     * @var SendPrepaymentReminderEmail
     */
    private $sendPrepaymentReminderEmail;

    /**
     * @var CheckIfSubscriptionIsActive
     */
    private $checkIfSubscriptionIsActive;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    public function __construct(
        MollieConfig $mollieConfig,
        Config $config,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SendPrepaymentReminderEmail $sendPrepaymentReminderEmail,
        CheckIfSubscriptionIsActive $checkIfSubscriptionIsActive,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->mollieConfig = $mollieConfig;
        $this->config = $config;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->sendPrepaymentReminderEmail = $sendPrepaymentReminderEmail;
        $this->checkIfSubscriptionIsActive = $checkIfSubscriptionIsActive;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
    }

    public function execute()
    {
        $today = (new \DateTime())->format('Y-m-d');

        $interval = new \DateInterval('P' . $this->config->daysBeforePrepaymentReminder() . 'D');
        $prepaymentDate = (new \DateTimeImmutable())->add($interval);

        $criteria = $this->searchCriteriaBuilderFactory->create();
        $criteria->addFilter('next_payment_date', $prepaymentDate->format('Y-m-d'), 'eq');
        $criteria->addFilter('last_reminder_date', $today, 'neq');

        $subscriptions = $this->subscriptionToProductRepository->getList($criteria->create());
        foreach ($subscriptions->getItems() as $subscription) {
            if (!$this->config->isPrepaymentReminderEnabled($subscription->getStoreId())) {
                continue;
            }

            if (!$this->checkIfSubscriptionIsActive->execute($subscription)) {
                continue;
            }

            $this->mollieConfig->addToLog(
                'info',
                sprintf(
                    'Sending prepayment reminder email for subscription "%s"',
                    $subscription->getEntityId()
                )
            );

            $this->sendPrepaymentReminderEmail->execute($subscription);

            $subscription->setLastReminderDate($today);
            $this->subscriptionToProductRepository->save($subscription);
        }
    }
}
