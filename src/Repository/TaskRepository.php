<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Token  as Token;

/**
 * Splash Background Tasks Repository
 */
class TaskRepository extends EntityRepository
{
    /**
     * @var QueryBuilder
     */
    private $builder;

    /**
     * Load Next Task To Perform from Db with Filter for Used Tokens
     *
     * @param array  $options   Search Options
     * @param string $tokenName Focus on a Specific Token (When Already Acquired)
     * @param bool   $static    Search fro Static Tasks
     *
     * @return null|Task
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function getNextTask(array $options, string $tokenName = null, bool $static = null): ?Task
    {
        //====================================================================//
        // Init Query Builder
        $this->builder = $this->createQueryBuilder("task");

        //====================================================================//
        // Setup Task Filters
        if (true == $static) {
            $this->setupStaticTasksFilter();
        } else {
            $this->setupNormalTasksFilter();
        }

        //====================================================================//
        // Setup Token Filters
        $this->setupTokenFilter($tokenName);

        //====================================================================//
        // Set Dates Parameters as TimeStamps
        $nowTimeStamp = (new \DateTime())->getTimestamp();

        //====================================================================//
        // Setup Query Token Parameters
        if (null == $tokenName) {
            $this->builder->setParameter('TokenExpireDate', ($nowTimeStamp - Token::SELFRELEASE_DELAY));
        } else {
            $this->builder->setParameter('TokenName', $tokenName);
        }

        //====================================================================//
        // Setup Task Priority Ordering
        $this->builder->orderBy("task.jobPriority", "DESC");

        //====================================================================//
        // Setup Query Time Parameters
        if (true == $static) {
            $this->builder->setParameter('Now', $nowTimeStamp);
        }

        $this->builder
            ->setParameter('MaxTry', $options["try_count"])                         // Max. Failled Executions
            ->setParameter('ErrorDate', ($nowTimeStamp - $options["error_delay"]))  // Delay to consider Task is In Error & Retry
            ->setParameter('MaxDate', ($nowTimeStamp - $options["try_delay"]))      // Delay Before retry an unfinished Task
            ->setMaxResults(1);

        return $this->builder->getQuery()->getOneOrNullResult();
    }

    /**
     * Load Tasks Summmary Array
     *
     * @param string $indexKey1 Your Custom Index Key 1
     * @param string $indexKey2 Your Custom Index Key 2
     *
     * @return array
     */
    public function getTasksSummary(string $indexKey1 = null, string $indexKey2 = null): array
    {
        //====================================================================//
        // Count User Running Tasks
        //====================================================================//
        $waitingQb = $this->createQueryBuilder("T")
            ->select('count(T.id)')
            ->where("T.running = 0")
            ->andWhere("T.finished = 0");
        $this->setupIndexKeys($waitingQb, $indexKey1, $indexKey2);

        //====================================================================//
        // Count User Running Tasks
        //====================================================================//
        $runningQb = $this->createQueryBuilder("T")
            ->select('count(T.id)')
            ->where("T.running = 1");
        $this->setupIndexKeys($runningQb, $indexKey1, $indexKey2);

        //====================================================================//
        // Count User Finished Tasks
        //====================================================================//
        $finishedQb = $this->createQueryBuilder("T")
            ->select('count(T.id)')
            ->where("T.running = 0")
            ->andWhere("T.finished = 1");
        $this->setupIndexKeys($finishedQb, $indexKey1, $indexKey2);

        //====================================================================//
        // Count User Total of Tasks
        //====================================================================//
        $totalQb = $this->createQueryBuilder("T")
            ->select('count(T.id)');
        $this->setupIndexKeys($totalQb, $indexKey1, $indexKey2);

        //====================================================================//
        // Count Total of Locked Tokens
        //====================================================================//
        $tokenQb = $this->getEntityManager()
            ->getRepository('SplashTaskingBundle:Token')
            ->createQueryBuilder("T")
            ->select('count(T.id)')
            ->where("T.locked = 1");

        //====================================================================//
        // Compte Results Array
        //====================================================================//
        return array(
            "Waiting" => $waitingQb->getQuery()->getSingleScalarResult(),
            "Running" => $runningQb->getQuery()->getSingleScalarResult(),
            "Finished" => $finishedQb->getQuery()->getSingleScalarResult(),
            "Total" => $totalQb->getQuery()->getSingleScalarResult(),
            "Token" => $tokenQb->getQuery()->getSingleScalarResult(),
        );
    }

    /**
     * Load Tasks By Index Keys or Token
     *
     * @param string $indexKey1 Your Custom Index Key 1
     * @param string $indexKey2 Your Custom Index Key 2
     * @param string $tokenName Your Custom Token
     *
     * @return array
     */
    public function getTasks(string $indexKey1 = null, string $indexKey2 = null, string $tokenName = null): array
    {
        //====================================================================//
        // Search for Tasks
        //====================================================================//
        $this->builder = $this->createQueryBuilder("T");

        //====================================================================//
        // Filter On IndexKeys
        if ((null != $indexKey1) || (null != $indexKey2)) {
            $this->setupIndexKeys($this->builder, $indexKey1, $indexKey2);
        }

        //====================================================================//
        // Filter On Token
        if (null != $tokenName) {
            $this->setupTokenFilter($tokenName);
        }

        return $this->builder->getQuery()->getResult();
    }

    /**
     * Load User Task Array, Sorted By Type
     *
     * @param string $key1    Your Custom Index Key 1
     * @param string $key2    Your Custom Index Key 2
     * @param array  $orderBy List Ordering
     * @param int    $limit   Limit Number of Items
     * @param int    $offset  Page Offset
     *
     * @return array User Task Summary Array
     */
    public function getTasksStatus(string $key1 = null, string $key2 = null, array $orderBy = array(), int $limit = 10, int $offset = 0)
    {
        //====================================================================//
        // Get List of Tasks By Types
        //====================================================================//
        $builder = $this
            ->createQueryBuilder("T")
            ->select(array('T.name', 'count(T.name) as Total', 'T.discriminator as Md5', 'T.settings'))
            ->groupBy("T.discriminator");

        $this
            ->setupIndexKeys($builder, $key1, $key2)
            ->setupOrderBy($builder, $orderBy)
            ->setupLimit($builder, $limit)
            ->setupOffset($builder, $offset);

        $status = $builder->getQuery()->getArrayResult();

        //====================================================================//
        // Add Tasks Counters
        //====================================================================//
        foreach ($status as &$taskStatus) {
            $taskStatus["Waiting"] = $this->getWaitingTasksCount(null, $taskStatus["Md5"], $key1, $key2);
            $taskStatus["Running"] = $this->getActiveTasksCount(null, $taskStatus["Md5"], $key1, $key2);
            $taskStatus["Finished"] = $taskStatus["Total"] - $taskStatus["Waiting"] - $taskStatus["Running"];
        }

        return $status;
    }

    /**
     * Return Number of Active Tasks
     *
     * @param string $tokenName Filter on a specific token Name
     * @param string $md5       Filter on a specific Discriminator
     * @param string $key1      Your Custom Index Key 1
     * @param string $key2      Your Custom Index Key 2
     *
     * @return int
     */
    public function getActiveTasksCount(string $tokenName = null, string $md5 = null, string $key1 = null, string $key2 = null)
    {
        //====================================================================//
        // Count Active/Running Tasks
        //====================================================================//
        $builder = $this->createQueryBuilder("T")
            ->select('count(T.id)')
            ->where("T.running = 1")
            ->andWhere("T.finished = 0");

        //====================================================================//
        // Filter Tasks
        //====================================================================//
        $this
            ->setupIndexKeys($builder, $key1, $key2)
            ->setupToken($builder, $tokenName)
            ->setupDiscriminator($builder, $md5);

        return $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Return Number of Active Tasks
     *
     * @param string $tokenName Filter on a specific token Name
     * @param string $md5       Filter on a specific Discriminator
     * @param string $key1      Your Custom Index Key 1
     * @param string $key2      Your Custom Index Key 2
     *
     * @return int
     */
    public function getWaitingTasksCount($tokenName = null, string $md5 = null, string $key1 = null, string $key2 = null): int
    {
        //====================================================================//
        // Count Active/Running Tasks
        //====================================================================//
        $builder = $this->createQueryBuilder("T")
            ->select('count(T.id)')
            ->where("T.running = 0")
            ->andWhere("T.finished = 0");

        //====================================================================//
        // Filter Tasks
        //====================================================================//
        $this
            ->setupIndexKeys($builder, $key1, $key2)
            ->setupToken($builder, $tokenName)
            ->setupDiscriminator($builder, $md5);

        return $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Return Number of Pending Tasks
     *
     * @param string $tokenName Filter on a specific token Name
     * @param string $md5       Filter on a specific Discriminator
     * @param string $key1      Your Custom Index Key 1
     * @param string $key2      Your Custom Index Key 2
     *
     * @return int
     */
    public function getPendingTasksCount(string $tokenName = null, string $md5 = null, string $key1 = null, string $key2 = null): int
    {
        //====================================================================//
        // Count Active/Running Tasks
        //====================================================================//
        $builder = $this->createQueryBuilder("T")
            ->select('count(T.id)')
            ->where("T.finished = 0");

        //====================================================================//
        // Filter Tasks
        //====================================================================//
        $this
            ->setupIndexKeys($builder, $key1, $key2)
            ->setupToken($builder, $tokenName)
            ->setupDiscriminator($builder, $md5);

        return $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Delete all Tasks finished for more than given delay
     *
     * @param int $maxAge Max Aging for Finished Tasks in Seconds
     *
     * @return int Count of Deleted Tasks
     */
    public function clean(int $maxAge): int
    {
        //==============================================================================
        // Prepare Max Age DateTime
        $maxDate = (new \DateTime())->getTimestamp() - $maxAge;
        $maxErrorDate = (new \DateTime())->getTimestamp() - (10 * $maxAge);

        //==============================================================================
        // Count Old Finished Tasks
        $finished = $this->createQueryBuilder("t")
            ->delete()
            ->where("t.finished = 1")
            ->andWhere("t.finishedAtTimeStamp < :maxage")
            ->andWhere("t.jobIsStatic != 1")
            ->setParameter(":maxage", $maxDate)
            ->getQuery()
            ->execute();

        //==============================================================================
        // Count In Error Tasks
        $error = $this->createQueryBuilder("t")
            ->delete()
            ->where("t.running = 1")
            ->where("t.finished = 0")
            ->andWhere("t.startedAtTimeStamp < :maxage")
            ->andWhere("t.jobIsStatic != 1")
            ->setParameter(":maxage", $maxErrorDate)
            ->getQuery()
            ->execute();

        return $finished + $error;
    }

    /**
     * Load List of Static Tasks
     *
     * @return array User Task Summary Array
     */
    public function getStaticTasks(): array
    {
        return $this->createQueryBuilder("t")
            ->where("t.jobIsStatic = 1")
            ->getQuery()
            ->execute();
    }

    /**
     * Flushes Entity Manager
     *
     * @param Task $task Task Item to Save
     */
    public function flush(Task $task): void
    {
        $this->_em->flush($task);
    }

    /**
     * Clear Entity Manager
     */
    public function clear(): void
    {
        $this->_em->clear();
    }
    
    //====================================================================//
    // *******************************************************************//
    //  Low Level Functions
    // *******************************************************************//
    //====================================================================//

    /**
     * Generate Active Tokens Query DQL
     *
     * @return string
     */
    private function getActiveTokensDQL(): string
    {
        return $this->_em->createQueryBuilder()
            ->select('tokens.name')
            ->from('Splash\Tasking\Entity\Token', 'tokens')
            ->where("tokens.locked = 1")                                    // Token is Locked
            ->andWhere("tokens.lockedAtTimeStamp > :TokenExpireDate")       // Token Started before Error Date
            ->getDQL();
    }

    /**
     * Select Tasks That Have Inactive Tokens or Given Token
     *
     * @param string $token Filter on a Specific Token
     *
     * @return $this
     */
    private function setupTokenFilter(string $token = null): self
    {
        //====================================================================//
        // Filter Task with Given Token
        if (null != $token) {
            $this->builder->andWhere('task.jobToken = :TokenName');

            return $this;
        }

        $this->builder->andWhere($this->builder->expr()->notIn('task.jobToken', $this->getActiveTokensDQL()));

        return $this;
    }

    /**
     * Select Tasks That Shall be Performed
     *
     * @return $this
     */
    private function setupNormalTasksFilter(): self
    {
        $this->builder
            //====================================================================//
            // Select Tasks That Shall be Performed
            ->add('where', $this->builder->expr()->orX(
                // Task Is Not Running
                'task.try = 0 AND task.running = 0',
                // If Task has Already been tried, but failled
                "task.try > 0 AND task.try < :MaxTry AND task.running = 0 AND task.startedAtTimeStamp <  :MaxDate",
                // If Task Timeout Exeeded
                "task.try < :MaxTry AND task.running = 1 AND task.startedAtTimeStamp < :ErrorDate"
            ))
            //====================================================================//
            // Select Tasks That Are Not Static
            ->andWhere('task.finished != 1')
            //====================================================================//
            // Select Tasks That Are Not Static
            ->andWhere('task.jobIsStatic != 1')
        ;

        return $this;
    }

    /**
     * Select Static Tasks That Shall be Performed
     *
     * @return $this
     */
    private function setupStaticTasksFilter(): self
    {
        $this->builder
            //====================================================================//
            // Select Tasks That Shall be Performed
            ->add('where', $this->builder->expr()->orX(
                // Task Is Not Running
                'task.try = 0 AND task.running = 0 AND task.finished = 0',
                // If Task has Already been tried, but failled
                "task.try > 0 AND task.try < :MaxTry AND task.running = 0 AND task.finished = 0 AND task.startedAtTimeStamp <  :MaxDate",
                // If Task Timeout Exeeded
                "task.try < :MaxTry AND task.running = 1 AND task.startedAtTimeStamp < :ErrorDate",
                // If Task Need Restart
                "task.running = 0 AND task.finished = 1 AND task.plannedAtTimeStamp <  :Now"
            ))
            //====================================================================//
            // Select Tasks That Are Not Static
            ->andWhere('task.jobIsStatic != 0')
        ;

        return $this;
    }

    /**
     * Setup Index Keys Filter on a QueryBuilder
     *
     * @param QueryBuilder $builder   Target QueryBuilder
     * @param string       $indexKey1 Your Custom Index Key 1
     * @param string       $indexKey2 Your Custom Index Key 2
     *
     * @return $this
     */
    private function setupIndexKeys(&$builder, string $indexKey1 = null, string $indexKey2 = null): self
    {
        if (null != $indexKey1) {
            $builder->andWhere("T.jobIndexKey1 = '".$indexKey1."'");
        }
        if (null != $indexKey2) {
            $builder->andWhere("T.jobIndexKey2 = '".$indexKey2."'");
        }

        return $this;
    }

    /**
     * Setup Order By Filter on a QueryBuilder
     *
     * @param QueryBuilder $builder Target QueryBuilder
     * @param array        $orderBy OrderBy Array
     *
     * @return $this
     */
    private function setupOrderBy(&$builder, array $orderBy = array()): self
    {
        if (0 == count($orderBy)) {
            return $this;
        }

        foreach ($orderBy as $key => $dir) {
            $builder->addOrderBy((string) $key, $dir);
        }

        return $this;
    }

    /**
     * Setup Limit Filter on a QueryBuilder
     *
     * @param QueryBuilder $builder Target QueryBuilder
     * @param int          $limit   Result Limit
     *
     * @return $this
     */
    private function setupLimit(&$builder, int $limit = null): self
    {
        if ($limit > 0) {
            $builder->setMaxResults($limit);
        }

        return $this;
    }

    /**
     * Setup Offset Filter on a QueryBuilder
     *
     * @param QueryBuilder $builder Target QueryBuilder
     * @param int          $offset  Pagination Offset
     *
     * @return $this
     */
    private function setupOffset(&$builder, int $offset = null) : self
    {
        if (!is_null($offset) && ($offset > 0)) {
            $builder->setFirstResult($offset);
        }

        return $this;
    }

    /**
     * Setup Token Filter on a QueryBuilder
     *
     * @param QueryBuilder $builder   Target QueryBuilder
     * @param string       $tokenName Filter on a specific token Name
     *
     * @return $this
     */
    private function setupToken(&$builder, string $tokenName = null) : self
    {
        if (null != $tokenName) {
            $builder
                ->andWhere("T.jobToken = :Token")
                ->setParameter('Token', $tokenName)
            ;
        }

        return $this;
    }

    /**
     * Setup Token Filter on a QueryBuilder
     *
     * @param QueryBuilder $builder Target QueryBuilder
     * @param string       $md5     Filter on a specific Discriminator
     *
     * @return $this
     */
    private function setupDiscriminator(&$builder, string $md5 = null) : self
    {
        if (null != $md5) {
            $builder
                ->andWhere("T.discriminator = :Md5")
                ->setParameter('Md5', $md5)
            ;
        }

        return $this;
    }
}
