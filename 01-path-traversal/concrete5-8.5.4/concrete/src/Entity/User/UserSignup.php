<?php
namespace Concrete\Core\Entity\User;

use Concrete\Core\Notification\Subject\SubjectInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="UserSignups"
 * )
 */
class UserSignup implements SubjectInterface
{
    /**
     * @ORM\Id @ORM\Column(type="integer", options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $usID;

    /**
     * @ORM\OneToOne(targetEntity="\Concrete\Core\Entity\User\User", inversedBy="signup"),
     * @ORM\JoinColumn(name="uID", referencedColumnName="uID")
     */
    protected $user;

    /**
     * @ORM\OneToMany(targetEntity="\Concrete\Core\Entity\Notification\UserSignupNotification", mappedBy="signup", cascade={"remove"}),
     */
    protected $notifications;

    /**
     * @ORM\ManyToOne(targetEntity="\Concrete\Core\Entity\User\User"),
     * @ORM\JoinColumn(name="createdBy", referencedColumnName="uID")
     */
    protected $createdBy;

    public function __construct(User $user, User $createdBy = null)
    {
        $this->user = $user;
        $this->createdBy = $createdBy;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return \DateTime
     */
    public function getNotificationDate()
    {
        return $this->user->getUserDateAdded();
    }

    /**
     * @return User|null
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param User $user
     */
    public function setCreatedBy(User $user) {
        $this->createdBy = $user;
    }

    /**
     * @return array|User[]
     */
    public function getUsersToExcludeFromNotification()
    {
        return is_object($this->createdBy) ? [$this->createdBy] : [];
    }
}
