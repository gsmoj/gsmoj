<?php
/**
 * 데이터베이스 백업/복구 도구.
 *
 * admin/backup.php에서 호출되며, 기존 HUSTOJ 인터페이스를 유지하면서 utf8mb4로 백업한다.
 */
class DatabaseTool
{
    private $handler;
    private $config = [
        'host'     => 'localhost',
        'port'     => 3306,
        'user'     => 'root',
        'password' => '',
        'database' => 'jol',
        'charset'  => 'utf8mb4',  // 한글/이모지까지 안전하게 저장하기 위한 문자셋
        'target'   => 'sql.sql'
    ];
    private $tables = [];
    private $error;
    private $begin;

    public function __construct($config = [])
    {
        $this->begin = microtime(true);
        $this->config = array_merge($this->config, (array)$config);

        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
            $this->handler = new PDO($dsn, $this->config['user'], $this->config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM,
            ]);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function backup($tables = [])
    {
        $this->setTables($tables);
        if (empty($this->tables)) {
            $this->error = '데이터베이스에 테이블이 없습니다!';
            return false;
        }

        $fp = @fopen($this->config['target'], 'w');
        if (!$fp) {
            $this->error = '대상 파일에 쓸 수 없습니다';
            return false;
        }

        // 복구 시 필요한 기본 정보와 외래키 검사 해제 구문을 파일 머리말에 기록한다.
        $head = "/*\r\nMySQL Database Backup Tool\r\n";
        $head .= "Server: {$this->config['host']}:{$this->config['port']}\r\n";
        $head .= "Database: {$this->config['database']}\r\n";
        $head .= "Charset: {$this->config['charset']}\r\n";
        $head .= "Date: " . date('Y-m-d H:i:s') . "\r\n*/\r\n";
        $head .= "SET NAMES '{$this->config['charset']}';\r\n";
        $head .= "SET FOREIGN_KEY_CHECKS=0;\r\n";
        fwrite($fp, $head);

        foreach ($this->tables as $table) {
            fwrite($fp, "-- ----------------------------\r\n");
            fwrite($fp, "-- Table structure for {$table}\r\n");
            fwrite($fp, "-- ----------------------------\r\n");
            fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\r\n");
            fwrite($fp, $this->getDDL($table) . "\r\n");
            fwrite($fp, "-- ----------------------------\r\n");
            fwrite($fp, "-- Records of {$table}\r\n");
            fwrite($fp, "-- ----------------------------\r\n");
            $this->dumpData($fp, $table);
        }

        fclose($fp);
        echo 'Backup Finished! Time ' . round((microtime(true) - $this->begin) * 1000, 2) . " ms";
        return true;
    }

    private function setTables($tables = [])
    {
        $this->tables = (!empty($tables) && is_array($tables)) ? $tables : $this->getTables();
    }

    // 단순 조회용 내부 헬퍼. 결과는 숫자 인덱스 배열로 반환된다.
    private function query($sql)
    {
        $stmt = $this->handler->query($sql);
        return $stmt ? $stmt->fetchAll() : [];
    }

    // 현재 DB에 존재하는 전체 테이블 목록을 가져온다.
    private function getTables()
    {
        $list = $this->query('SHOW TABLES');
        return array_column($list, 0);
    }

    // 테이블 생성 SQL을 백업 파일에 그대로 남기기 위해 SHOW CREATE TABLE을 사용한다.
    private function getDDL($table)
    {
        $ddl = $this->query("SHOW CREATE TABLE `{$table}`");
        return $ddl[0][1] . ';';
    }

    // 테이블의 모든 행을 INSERT 문으로 변환해 백업 파일에 기록한다.
    private function dumpData($fp, $table)
    {
        $columns = array_column($this->query("SHOW COLUMNS FROM `{$table}`"), 0);
        $columnList = '`' . implode('`,`', $columns) . '`';
        $stmt = $this->handler->query("SELECT * FROM `{$table}`", PDO::FETCH_NUM);

        while ($row = $stmt->fetch()) {
            $values = array_map([$this->handler, 'quote'], $row);
            $sql = "INSERT INTO `{$table}` ({$columnList}) VALUES (" . implode(',', $values) . ");\r\n";
            fwrite($fp, $sql);
        }
    }

    public function restore($path = '')
    {
        if (!file_exists($path)) {
            $this->error = 'SQL 파일이 존재하지 않습니다!';
            return false;
        }

        $sqlList = $this->parseSQL($path);
        try {
            foreach ($sqlList as $sql) {
                if (trim($sql) !== '') {
                    $this->handler->exec($sql);
                }
            }
            echo '복구 성공! 걸린 시간 ' . round((microtime(true) - $this->begin) * 1000, 2) . " ms";
            return true;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    // 백업 파일에서 주석을 제거하고 세미콜론 단위의 SQL 문장 배열로 나눈다.
    private function parseSQL($path)
    {
        $sql = file_get_contents($path);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $sql = preg_replace('/--.*(\r?\n)/', '', $sql);
        $sql = preg_replace('/^\s*#.*$/m', '', $sql);
        $statements = preg_split('/;\s*[\r\n]+/', $sql);
        return array_filter(array_map('trim', $statements));
    }

    public function getError()
    {
        return $this->error;
    }
}
