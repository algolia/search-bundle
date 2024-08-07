<?php

namespace Algolia\SearchBundle\TestApp\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table('posts')]
class Post
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column('id', 'integer')]
    #[Groups(['searchable'])]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(type: 'text')]
    private $content;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime')]
    private $publishedAt;

    /**
     * @var Comment[]|ArrayCollection
     */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: 'Comment', orphanRemoval: true)]
    #[ORM\OrderBy(['publishedAt' => 'DESC'])]
    private $comments;

    public function __construct(array $attributes = [])
    {
        $this->id          = $attributes['id'] ?? null;
        $this->title       = $attributes['title'] ?? null;
        $this->content     = $attributes['content'] ?? null;
        $this->publishedAt = $attributes['publishedAt'] ?? new \DateTime();
        $this->comments    = isset($attributes['comments']) ? new ArrayCollection($attributes['comments']) : new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    #[Groups(['searchable'])]
    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    #[Groups(['searchable'])]
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTime $publishedAt)
    {
        $this->publishedAt = $publishedAt;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function addComment(Comment $comment)
    {
        $comment->setPost($this);
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
        }
    }

    public function removeComment(Comment $comment)
    {
        $comment->setPost(null);
        $this->comments->removeElement($comment);
    }
}
