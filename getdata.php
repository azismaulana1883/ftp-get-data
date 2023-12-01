<?php
// Informasi SFTP
$sftpHost = "eadaptor-sftp-trn.logistical.one";
$sftpUsername = "sftp-panbrothers";
$sftpPassword = "sXvozm3VjAMY36o8kgj4m";
$sftpRemoteDirectory = '/outbound/';

// Lokasi direktori lokal yang akan dicopy
$localDirectory = 'C:/test_ftp/';

// Set batas waktu eksekusi menjadi 300 detik (5 menit)
set_time_limit(300);

// Fungsi untuk mengunduh file dari SFTP menggunakan PHP cURL
function downloadFilesFromSFTP($sftpHost, $sftpUsername, $sftpPassword, $sftpRemoteDirectory, $localDirectory) {
    // Inisialisasi cURL
    $curl = curl_init();

    // Set opsi cURL untuk mengakses SFTP
    $sftpUrl = "sftp://$sftpUsername:$sftpPassword@$sftpHost$sftpRemoteDirectory";
    curl_setopt($curl, CURLOPT_URL, $sftpUrl);
    curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_SFTP);
    curl_setopt($curl, CURLOPT_USERPWD, "$sftpUsername:$sftpPassword");
    curl_setopt($curl, CURLOPT_DIRLISTONLY, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    // Eksekusi cURL dan ambil daftar file
    $fileList = curl_exec($curl);

    // Periksa apakah ada kesalahan
    if (curl_errno($curl)) {
        die('Error: ' . curl_error($curl));
    }

    // Tutup cURL
    curl_close($curl);

    // Pisahkan daftar file
    $files = explode("\n", $fileList);

    // Hapus entri kosong
    $files = array_filter($files);

    // Loop melalui file dan unduh ke direktori lokal
    foreach ($files as $file) {
        $remoteFile = "$sftpUrl$file";
        $localFile = $localDirectory . $file;

        // Hanya unduh jika file belum ada di direktori lokal dan berekstensi .txt
        if (!file_exists($localFile) && pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
            // Unduh file menggunakan cURL
            $fileHandle = fopen($localFile, 'w');
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $remoteFile);
            curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_SFTP);
            curl_setopt($curl, CURLOPT_USERPWD, "$sftpUsername:$sftpPassword");
            curl_setopt($curl, CURLOPT_FILE, $fileHandle);
            curl_exec($curl);

            // Periksa apakah ada kesalahan
            if (curl_errno($curl)) {
                echo "Gagal mengunduh file $file dari SFTP. Error: " . curl_error($curl) . "\n";
            } else {
                echo "File $file berhasil diunduh dan disimpan di $localDirectory.\n";

                // Ganti nama file di SFTP dengan menambahkan -DONE
                $renameCurl = curl_init();
                $renameUrl = "$sftpUrl$file-DONE";
                curl_setopt($renameCurl, CURLOPT_URL, $renameUrl);
                curl_setopt($renameCurl, CURLOPT_PROTOCOLS, CURLPROTO_SFTP);
                curl_setopt($renameCurl, CURLOPT_USERPWD, "$sftpUsername:$sftpPassword");
                curl_setopt($renameCurl, CURLOPT_CUSTOMREQUEST, 'RENAME');
                curl_setopt($renameCurl, CURLOPT_HEADER, false);

                $renameResult = curl_exec($renameCurl);

                if ($renameResult) {
                    echo "File $file diubah nama menjadi $file-DONE di SFTP.\n";
                } else {
                    echo "Gagal mengubah nama file $file di SFTP. Error: " . curl_error($renameCurl) . "\n";
                }

                // Tutup cURL rename
                curl_close($renameCurl);
            }

            // Tutup cURL dan file handle
            curl_close($curl);
            fclose($fileHandle);
        } else {
            echo "File $file tidak diunduh. Mungkin sudah ada di direktori lokal atau bukan file berekstensi .txt.\n";
        }
    }
}

// Pindahkan file dari SFTP ke direktori lokal
downloadFilesFromSFTP($sftpHost, $sftpUsername, $sftpPassword, $sftpRemoteDirectory, $localDirectory);

echo 'Files copied from SFTP to local directory successfully!';
?>
