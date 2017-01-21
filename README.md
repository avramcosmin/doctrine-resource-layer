# Doctrine Resource Layer

Unfortunately Doctrine is only managing relationships that get changed on the owning side. This means that any change
 made on the inverse side will only be persisted if the same change is made on the owning side.

Because Doctrine is not handling this, `Doctrine Resource Layer` will fill this gap by offering a easy way to persist
 relationships without the need of making sure this is done on the right side of the relationship.

## Symfony services

`Doctrine Resource Layer` make available two services. A `Resource` and a `Response` service. Just add the following
 lines inside `app/config/services.yml`:
 
```yaml
    mindlahus.v1.response:
        class:        Mindlahus\Service\ResponseService
        arguments: ["@request_stack"]
    mindlahus.v1.resource:
        class:        Mindlahus\Service\ResourceService
        arguments: ["@request_stack", "@service_container"]
```