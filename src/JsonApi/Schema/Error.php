<?php
namespace WoohooLabs\Yin\JsonApi\Schema;

class Error
{
    use MetaTrait;
    use LinksTrait;

    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $status;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $detail;

    /**
     * @var \WoohooLabs\Yin\JsonApi\Schema\ErrorSource
     */
    private $source;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * @param string $detail
     * @return $this
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;
        return $this;
    }

    /**
     * @return \WoohooLabs\Yin\JsonApi\Schema\ErrorSource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param \WoohooLabs\Yin\JsonApi\Schema\ErrorSource $source
     * @return $this
     */
    public function setSource(ErrorSource $source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @return array
     */
    public function transform()
    {
        $content = [];

        if ($this->id) {
            $content["id"] = $this->id;
        }

        if (empty($this->meta) === false) {
            $content["meta"] = $this->meta;
        }

        if ($this->links) {
            $content["links"] = $this->links->transform();
        }

        if ($this->status) {
            $content["status"] = $this->status;
        }

        if ($this->code) {
            $content["code"] = $this->code;
        }

        if ($this->title) {
            $content["title"] = $this->title;
        }

        if ($this->detail) {
            $content["detail"] = $this->detail;
        }

        if ($this->source) {
            $content["source"] = $this->source->transform();
        }

        return $content;
    }
}
