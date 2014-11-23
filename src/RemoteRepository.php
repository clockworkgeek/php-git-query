<?php
namespace GitQuery;

class RemoteRepository extends Repository
{

    /**
     * @var Connector
     */
    private $connector;

    /**
     * SHA1 values keyed by ref string
     * 
     * @var array
     */
    private $references = null;

    public function __construct($connector)
    {
        parent::__construct();
        $this->connector = $connector;
    }

    /**
     * Fetches associative array of heads and tags
     * 
     * If returned array is empty then there's probably an error
     * as a valid server should return at least HEAD.
     * 
     * @param string $process
     * @return array
     */
    private function readReferences($process)
    {
        $conn = $this->connector;
        $conn->process = $process;
        $refs = array();
        while (($line = $conn->readLine())) {
            if (! $line || ($line[0] === '#')) {
                while ($conn->readLine()); // skip empty line or comment
                continue;
            }

            sscanf($line, '%s %s', $sha1, $ref);
            $refs[$ref] = $sha1;
        }
        return $refs;
    }

    public function getReferences($process = 'git-upload-pack')
    {
        if (is_null($this->references)) {
            $this->references = $this->readReferences($process);
        }
        return $this->references;
    }

    public function head()
    {
        $refs = $this->getReferences();
        if (isset($refs[HEAD])) {
            return new Commit($refs[HEAD]);
        }
        return null;
    }

    public function getContentURL($verb, $sha1)
    {
        return null;
    }

    /**
     * Download a packfile to fill in gaps between $want and $have, and save to a temp file.
     * 
     * @param array $want SHA1 values to request or empty to retrieve all available
     * @param array $have SHA1 values stored locally or empty to clone all history
     * @return string Temporary filename of resulting packfile
     */
    public function fetch($want = array(), $have = array())
    {
        if (! $want) {
            $want = $this->getReferences();
            if (! $want) {
                return;
            }
        }
        $conn = $this->connector;
        foreach ($want as $sha1) {
            $conn->writeLine(sprintf("want %s\n", $sha1));
        }
        $conn->writeLine();
        foreach ($have as $sha1) {
            $conn->writeLine(sprintf("have %s\n", $sha1));
        }
        $conn->writeLine("done\n");
        $conn->flush();

        while (true) {
            $line = $conn->readLine();
            if (@$line[0] !== '#') break;
        }
        if (! sscanf($line, 'ACK %[0-9a-f]', $sha1) && ($line != "NAK\n")) {
            throw new \UnexpectedValueException('Unrecognised server response: '.$line);
        }

        // whether server ACK'd $sha1 or NAK'd all $have it will now send a packfile
        $packfilename = tempnam(sys_get_temp_dir(), 'packfile');
        while (($data = $conn->read(0x10000))) {
            file_put_contents($packfilename, $data, FILE_APPEND);
        }
        return $packfilename;
    }
}
