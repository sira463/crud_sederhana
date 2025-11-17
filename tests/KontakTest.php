<?php
use PHPUnit\Framework\TestCase;

class KontakTest extends TestCase
{
    private $apiUrl = 'http://localhost/crud_sederhana/api.php';

    private function request($method, $data = [])
    {
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public function testCreateReadUpdateDelete()
    {
        // 1. Create
        $newContact = ['nama'=>'Test User','telepon'=>'081234567890','email'=>'test@gmail.com'];
        $res = $this->request('POST', $newContact);
        $this->assertEquals('success', $res['status']);
        $this->assertEquals('Kontak berhasil disimpan', $res['message']);

        // 2. Read (search)
        $res = $this->request('GET', ['search'=>'Test User']);
        $this->assertEquals('success', $res['status']);
        $found = false;
        $id = null;
        foreach($res['data'] as $c){
            if($c['nama'] === 'Test User'){
                $found = true;
                $id = $c['id'];
                break;
            }
        }
        $this->assertTrue($found, "Kontak baru ditemukan");

        // 3. Update
        $updatedContact = ['id'=>$id,'nama'=>'Updated User','telepon'=>'081234567891','email'=>'update@gmail.com'];
        $res = $this->request('PUT', $updatedContact);
        $this->assertEquals('success', $res['status']);
        $this->assertEquals('Kontak berhasil diperbarui', $res['message']);

        // 4. Delete
        $res = $this->request('DELETE', ['id'=>$id]);
        $this->assertEquals('success', $res['status']);
        $this->assertEquals('Kontak berhasil dihapus', $res['message']);
    }
}
