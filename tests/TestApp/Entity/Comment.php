<?php

namespace Algolia\SearchBundle\TestApp\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('comments')]
class Comment
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column('id', 'integer')]
    private $id;

    /**
     * @var Post
     */
    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private $post;

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

    public function __construct(array $attributes = [])
    {
        $this->id          = $attributes['id'] ?? null;
        $this->content     = $attributes['content'] ?? null;
        $this->publishedAt = $attributes['publishedAt'] ?? new \DateTime();
        $this->post        = $attributes['post'] ?? null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTime $publishedAt)
    {
        $this->publishedAt = $publishedAt;
    }

    public function getPost()
    {
        return $this->post;
    }

    public function setPost(Post $post)
    {
        $this->post = $post;
    }
}
