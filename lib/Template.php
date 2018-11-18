<?php

class Template
{

	protected const BEGIN = '<!--BEGIN-->';
	protected const END   = '<!--END-->';

	protected const BLOCK_BEGIN = '<!--';
	protected const BLOCK_END   = '-->';

	protected const ELEMENT_BEGIN = '{';
	protected const ELEMENT_END   = '}';

	protected const HREF_BEGIN = 'href=":';
	protected const HREF_END   = '"';

	protected const PARSE_BEGIN = [self::BLOCK_BEGIN, self::ELEMENT_BEGIN, self::HREF_BEGIN];

	/**
	 * @var int
	 */
	protected $blocks_depth = 0;

	/**
	 * @var string
	 */
	protected $buffer;

	/**
	 * @var integer
	 */
	protected $buffer_length;

	/**
	 * @var string
	 */
	protected $file_name;

	/**
	 * @var bool
	 */
	protected $force_compile;

	/**
	 * The context object for execute
	 * If null : no execution
	 *
	 * @var object
	 */
	protected $object;

	/**
	 * @var string
	 */
	protected $result = '';

	public function __construct(string $file_name, object $object = null)
	{
		$this->file_name = $file_name;
		$this->object    = $object;
	}

	protected function block(int $position) : int
	{
		$end_length = strlen('<!--end-->');
		if (substr($this->buffer, $position, $end_length) === '<!--end-->') {
			$this->result       .= '<?php } ?>';
			$this->blocks_depth --;
			$position           += $end_length;
		}
		else {
			$object        = '$object' . ($this->blocks_depth ++);
			$sub_object    = '$object' . $this->blocks_depth;
			$position     += strlen(static::BLOCK_BEGIN);
			$stop          = strpos($this->buffer, static::BLOCK_END, $position);
			$property_name = substr($this->buffer, $position, $stop - $position);
			$this->result .= "<?php foreach ($object->$property_name as $sub_object) { ?>";
			$position      = $stop + strlen(static::BLOCK_END);
		}
		$this->skipLine($position);
		return $position;
	}

	protected function compile()
	{
		$this->buffer = file_get_contents($this->file_name);

		$position      = $this->startPosition();
		$last_position = $position;

		while (($position = $this->nextPosition($position)) < $this->buffer_length) {
			$this->copy($last_position, $position);
			switch ($this->buffer[$position]) {
				case '<': $position = $this->block($position);   break;
				case '{': $position = $this->element($position); break;
				case 'h': $position = $this->link($position);    break;
			}
			$last_position = $position;
		}
		$this->copy($last_position, $this->buffer_length);
	}

	protected function compiledFileName(string $file_name) : string
	{
		$position  = strrpos($file_name, '/');
		$directory = substr($file_name, 0, $position) . '/cache';
		$file_name = substr($file_name, $position + 1);
		if (!file_exists($directory)) {
			mkdir($directory);
		}
		return $directory . '/' . str_replace('.html', '.php', $file_name);
	}

	protected function copy(int $last_position, int $position)
	{
		if ($last_position < $position) {
			$this->result .= substr($this->buffer, $last_position, $position - $last_position);
		}
	}

	protected function element(int $position) : int
	{
		$position ++;
		$stop     = strpos($this->buffer, static::ELEMENT_END, $position);
		$element  = substr($this->buffer, $position, $stop - $position);
		$object   = '$object' . $this->blocks_depth;
		// function
		if (substr($element, 0, 2) === '§') {
			$element       = substr($element, 2);
			$this->result .= "<?=$object->$element()?>";
		}
		// include
		elseif (substr($element, -5) === '.html') {
			$directory = (strpos($this->file_name, '/') !== false)
				? (substr($this->file_name, 0, strrpos($this->file_name, '/')) . '/')
				: '';
			$this->include($directory . $element);
			$include = true;
		}
		// property
		else {
			$this->result .= "<?=$object->$element?>";
		}
		$position = $stop + strlen(static::ELEMENT_END);
		if (isset($include)) {
			$this->skipLine($position);
		}
		return $position;
	}

	protected function execute(string $compiled_file_name)
	{
		/** @noinspection PhpUnusedLocalVariableInspection used into compiled file name */
		$object0 = $this->object;
		/** @noinspection PhpIncludeInspection dynamic */
		include $compiled_file_name;
	}

	protected function include($file_name)
	{
		$template               = new static($file_name);
		$template->blocks_depth = $this->blocks_depth;
		$template->parse(false, $this->force_compile);
		$compiled_file_name = $this->compiledFileName($file_name);
		$this->result      .= "<?php include '$compiled_file_name' ?>";
	}

	protected function link(int $position) : int
	{
		$position     += strlen(static::HREF_BEGIN);
		$stop          = strpos($this->buffer, static::HREF_END, $position);
		$property_name = substr($this->buffer, $position, $stop - $position);
		$object        = '$object' . $this->blocks_depth;
		$this->result .= substr(static::HREF_BEGIN, 0, -1)
			. "<?=$object->$property_name?>"
			. static::HREF_END;
		return $stop + strlen(static::HREF_END);
	}

	protected function nextPositionOf(string $pattern, int $position) : int
	{
		$position = strpos($this->buffer, $pattern, $position);
		return ($position === false) ? $this->buffer_length : $position;
	}

	protected function nextPosition(int $position) : int
	{
		$positions = [];
		foreach (static::PARSE_BEGIN as $to_parse) {
			$positions[] = $this->nextPositionOf($to_parse, $position);
		}
		return min($positions);
	}

	public function parse(bool $execute = true, bool $force_compile = false) : string
	{
		$compiled_file_name  = $this->compiledFileName($this->file_name);
		$this->force_compile = $force_compile;
		if (
			$force_compile
			|| !file_exists($compiled_file_name)
			|| (filemtime($compiled_file_name) <= filemtime($this->file_name))
		) {
			$this->compile();
			file_put_contents($compiled_file_name, $this->result);
		}
		if ($execute && $this->object) {
			$this->execute($compiled_file_name);
		}
		return $this->result ?: file_get_contents($compiled_file_name);
	}

	protected function skipLine(int &$position)
	{
		if ($this->buffer[$position] === "\n") {
			do {
				$position ++;
			}
			while (strpos(" \t", $this->buffer[$position]) !== false);
		}
	}

	protected function startPosition() : int
	{
		if (($begin_position = strpos($this->buffer, static::BEGIN)) === false) {
			$position            = 0;
			$this->buffer_length = strlen($this->buffer);
		}
		else {
			$position            = $begin_position + strlen(static::BEGIN);
			$this->buffer_length = strpos($this->buffer, static::END);
			if ($this->buffer[$position] === "\n") {
				$position ++;
			}
		}
		return $position;
	}

}
