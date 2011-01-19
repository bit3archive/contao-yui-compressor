<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  InfinitySoft 2011
 * @author     Tristan Lins <tristan.lins@infinitysoft.de>
 * @package    Compression API
 * @license    LGPL
 * @filesource
 */

/**
 * Class AbstractYuiCompressor
 *
 * abstract wrapper class for the yui compressor (http://developer.yahoo.com/yui/compressor/)
 * @copyright  InfinitySoft 2011
 * @author     Tristan Lins <tristan.lins@infinitysoft.de>
 * @package    Compression API
 */
class AbstractYuiCompressor extends AbstractMinimizer implements CssMinimizer
{
	/**
	 * The current code type.
	 */
	private $strType;
	
	
	public function __construct($strType)
	{
		parent::__construct();
		$this->strType = $strType;
		$this->configure(array
		(
			'cmd' => 'yui-compressor',
			'lineBreak' => 0,
			'nomunge' => false,
			'preserve-semi' => false,
			'disable-optimizations' => false
		));
	}
	
	
	/**
	 * Generate the command.
	 * 
	 * @param mixed ...
	 * A list of optional arguments that are appended to the command.
	 * 
	 * @return string
	 */
	private function generateCommand()
	{
		$strCmd  = escapeshellcmd($this->arrConfig['cmd']);
		$strCmd .= ' --type ' . escapeshellarg($this->strType);
		$strCmd .= ' --charset utf8';
		if ($this->arrConfig['lineBreak'] > 0)
		{
			$strCmd .= ' --line-break ' . escapeshellarg($this->arrConfig['lineBreak']);
		}
		if ($this->arrConfig['nomunge'])
		{
			$strCmd .= ' --nomunge';
		}
		if ($this->arrConfig['preserve-semi'])
		{
			$strCmd .= ' --preserve-semi';
		}
		if ($this->arrConfig['disable-optimizations'])
		{
			$strCmd .= ' --disable-optimizations';
		}
		if (func_num_args() > 0)
		{
			foreach (func_get_args() as $strArg)
			{
				$strCmd .= ' ' . escapeshellarg($strArg);
			}
		}
		return $strCmd;
	}
	
	
	/**
	 * Execute the yui compressor.
	 * 
	 * @param string $strCmd
	 * The command that should be executed.
	 * 
	 * @param string $strData
	 * Optionla data that is written to the process stdin.
	 * 
	 * @throws Exception
	 */
	private function executeYui($strCmd, $strData = false)
	{
		// execute yui-compressor
		$procYUI = proc_open(
			$strCmd,
			array(
				0 => array("pipe", "r"),
				1 => array("pipe", "w"),
				2 => array("pipe", "w")
			),
			$arrPipes);
		if ($procYUI === false)
		{
			$strErr = sprintf("yui compressor could not be started!<br/>\ncmd: %s", $strCmd);
			$this->log($strErr, get_class($this) . '::executeYui', TL_ERROR);
			throw new Exception($strErr);
		}
		// write contents
		if ($strData !== false)
		{
			fwrite($arrPipes[0], $strCode);
		}
		// close stdin
		fclose($arrPipes[0]);
		// read and close stdout
		$strOut = stream_get_contents($arrPipes[1]);
		fclose($arrPipes[1]);
		// read and close stderr
		$strErr = stream_get_contents($arrPipes[2]);
		fclose($arrPipes[2]);
		// wait until yui-compressor terminates
		$intCode = proc_close($procYUI);
		if ($intCode != 0)
		{
			$strErr = sprintf("Execution of yui compressor failed!<br/>\ncmd: %s", $strCmd);
			$this->log($strErr, get_class($this) . '::executeYui', TL_ERROR);
			throw new Exception($strErr);
		}
		return $strOut;
	}
	

	/**
	 * (non-PHPdoc)
	 * @see Minimizer::minimize($strSource, $strTarget)
	 */
	public function minimize($strSource, $strTarget)
	{
		return $this->executeYui($this->generateCommand('-o', $strTarget, $strSource));
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see Minimizer::minimizeFile($strSource)
	 */
	public function minimizeFile($strSource)
	{
		return $this->executeYui($this->generateCommand($strSource));
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Minimizer::minimizeCode($strCode)
	 */
	public function minimizeCode($strCode)
	{
		return $this->executeYui($this->generateCommand(), $strCode);
	}
}
?>