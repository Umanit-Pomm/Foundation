<?php
/*
 * This file is part of the Pomm's Foundation package.
 *
 * (c) 2014 - 2017 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\Foundation;

use PommProject\Foundation\Session\ResultHandler;

/**
 * ResultIterator
 *
 * This is a simple iterator on database results. It manages a result database
 * resource.
 *
 * @package   Foundation
 * @copyright 2014 - 2017 Grégoire HUBERT
 * @author    Grégoire HUBERT
 * @license   X11 {@link http://opensource.org/licenses/mit-license.php}
   @see       ResultIteratorInterface
 * @see       \JsonSerializable
 */
class ResultIterator implements ResultIteratorInterface, \JsonSerializable
{
    private $position;
    protected $result;
    private $rows_count;

    /**
     * __construct
     *
     * Constructor
     *
     * @param  ResultHandler $result
     */
    public function __construct(ResultHandler $result)
    {
        $this->result   = $result;
        $this->position = 0;
    }

    /**
     * __destruct
     *
     * Closes the cursor when the collection is cleared.
     */
    public function __destruct()
    {
        $this->result->free();
    }

    /**
     * seek
     *
     * Alias for get(), required to be a Seekable iterator.
     *
     * @param  int $index
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function seek($index)
    {
        return $this->get($index);
    }

    /**
     * get
     *
     * Return a particular result. An array with converted values is returned.
     * pg_fetch_array is muted because it produces untrappable warnings on
     * errors.
     *
     * @param  integer $index
     * @return array
     */
    public function get($index)
    {
        return $this->result->fetchRow($index);
    }

    /**
     * has
     *
     * Return true if the given index exists false otherwise.
     *
     * @param  integer $index
     * @return boolean
     */
    public function has($index)
    {
        return (bool) ($index < $this->count());
    }

    /**
     * count
     *
     * @see    \Countable
     * @return integer
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        if ($this->rows_count == null) {
            $this->rows_count = $this->result->countRows();
        }

        return $this->rows_count;
    }

    /**
     * rewind
     *
     * @see \Iterator
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * current
     *
     * @see \Iterator
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return (($this->rows_count != null && $this->rows_count > 0 ) || !$this->isEmpty())
            ? $this->get($this->position)
            : null
            ;
    }

    /**
     * key
     *
     * @see \Iterator
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    /**
     * next
     *
     * @see \Iterator
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        ++$this->position;
    }

    /**
     * valid
     *
     * @see \Iterator
     * @return boolean
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->has($this->position);
    }

    /**
     * isFirst
     * Is the iterator on the first element ?
     * Returns null if the iterator is empty.
     *
     * @return boolean|null
     */
    public function isFirst()
    {
        return !$this->isEmpty()
            ? $this->position === 0
            : null
            ;
    }

    /**
     * isLast
     *
     * Is the iterator on the last element ?
     * Returns null if the iterator is empty.
     *
     * @return boolean|null
     */
    public function isLast()
    {
        return !$this->isEmpty()
            ? $this->position === $this->count() - 1
            : null
            ;
    }

    /**
     * isEmpty
     *
     * Is the collection empty (no element) ?
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return (($this->rows_count != null && $this->rows_count === 0 ) || $this->count() === 0);
    }

    /**
     * isEven
     *
     * Is the iterator on an even position ?
     *
     * @return boolean
     */
    public function isEven()
    {
        return ($this->position % 2) === 0;
    }

    /**
     * isOdd
     *
     * Is the iterator on an odd position ?
     *
     * @return boolean
     */
    public function isOdd()
    {
        return ($this->position % 2) === 1;
    }

    /**
     * getOddEven
     *
     * Return 'odd' or 'even' depending on the element index position.
     * Useful to style list elements when printing lists to do
     * <li class="line_<?php $list->getOddEven() ?>">.
     *
     * @return String
     */
    public function getOddEven()
    {
        return $this->position % 2 === 1 ? 'odd' : 'even';
    }

    /**
     * slice
     *
     * Extract an array of values for one column.
     *
     * @param  string $field
     * @return array  values
     */
    public function slice($field)
    {
        if ($this->isEmpty()) {
            return [];
        }

        return $this->result->fetchColumn($field);
    }

    /**
     * extract
     *
     * Dump an iterator.
     * This actually stores all the results in PHP allocated memory.
     * THIS MAY USE A LOT OF MEMORY.
     *
     * @return array
     */
    public function extract()
    {
        $results = [];

        foreach ($this as $result) {
            $results[] = $result;
        }

        return $results;
    }

    /**
     * jsonSerialize
     *
     * @return mixed
     * @see \JsonSerializable
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->extract();
    }
}
