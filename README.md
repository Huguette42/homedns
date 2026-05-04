# Homedns

Application web simple de gestion DNS.

## Fonctionnement

- résout les DNS des domaines listés
- teste un accès HTTP sur le port 80 de l’IP résolue
- affiche **OK** si une réponse HTML est reçue, sinon **KO**

## Fichiers importants

- [index.php](index.php) : point d’entrée principal
- [src/DnsResolver.php](src/DnsResolver.php) : résolution DNS
- [src/helpers.php](src/helpers.php) : fonctions utilitaires
- [docker-compose.yml](docker-compose.yml) : exécution Docker

## Lancement

```bash
docker compose up -d
```

Puis ouvrir :

```text
http://localhost:8080
```

## Notes

- Composer n’est plus utilisé.
- La prévisualisation serveur a été supprimée.
- Si un site répond en HTML sur le port 80, il apparaît en **OK**.
