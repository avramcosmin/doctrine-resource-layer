# Doctrine Resource Layer v.2.0.1

Unfortunately Doctrine is only managing relationships that get changed on the owning side. This means that any change
 made on the inverse side will only be persisted if the same change is made on the owning side.

Because Doctrine is not handling this, `Doctrine Resource Layer` will fill this gap by offering a easy way to persist
 relationships without the need of making sure this is done on the right side of the relationship.