<?php

namespace Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use \Entity\Course;
/**
 * CourseRepository
 *
 */
class CourseRepository extends EntityRepository
{
    /**
     * Get all users that are registered in the course. No matter the status
     *
     * @param Course $course
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedUsers(Course $course)
    {
        $queryBuilder = $this->createQueryBuilder('a');

        // Selecting user info.
        $queryBuilder->select('DISTINCT u');

        // Loading EntityUser.
        $queryBuilder->from('Entity\User', 'u');

        // Selecting courses for users.
        $queryBuilder->innerJoin('u.courses', 'c');

        //@todo check app settings
        $queryBuilder->add('orderBy', 'u.lastname ASC');

        $wherePart = $queryBuilder->expr()->andx();

        // Get only users subscribed to this course
        $wherePart->add($queryBuilder->expr()->eq('c.cId', $course->getId()));

        // $wherePart->add($queryBuilder->expr()->eq('c.status', $status));

        $queryBuilder->where($wherePart);
        //$q = $queryBuilder->getQuery();
        //return $q->execute();
        return $queryBuilder;
    }

    /**
     * Gets students subscribed in the course
     *
     * @param Course $course
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedStudents(Course $course)
    {
        $queryBuilder = $this->getSubscribedUsers($course);
        $wherePart = $queryBuilder->expr()->andx();
        $wherePart->add($queryBuilder->expr()->eq('c.status', STUDENT));
        return $queryBuilder;
    }

    /**
     * Gets the students subscribed in the course
     * @param \Entity\Course $course
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedCoaches(Course $course)
    {
        $queryBuilder = $this->getSubscribedUsers($course);
        return $queryBuilder;
    }

    /**
     *
     * Gets the teachers subscribed in the course
     * @param Course $course
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedTeachers(Course $course)
    {
        $queryBuilder = $this->getSubscribedUsers($course);
        $wherePart = $queryBuilder->expr()->andx();
        $wherePart->add($queryBuilder->expr()->eq('c.status', COURSEMANAGER));

        return $queryBuilder;
    }


}
