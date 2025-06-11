<?php

namespace App\EventListener;

use App\Entity\Produit;
use App\Entity\User;
use App\Message\NotificationMessage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Produit::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Produit::class)]
#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Produit::class)]
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: Produit::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdateUser', entity: User::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdateUser', entity: User::class)]
class EntityChangeListener
{
    private bool $userWasDeactivated = false;
    private array $removedProductData = [];

    public function __construct(
        private MessageBusInterface $bus
    ) {
    }

    public function postPersist(Produit $produit, PostPersistEventArgs $event): void
    {
        $this->bus->dispatch(new NotificationMessage(
            'CREATION',
            'Produit: ' . $produit->getName(),
            $produit->getId()
        ));
    }

    public function postUpdate(Produit $produit, PostUpdateEventArgs $event): void
    {
        $this->bus->dispatch(new NotificationMessage(
            'MODIFICATION',
            'Produit: ' . $produit->getName(),
            $produit->getId()
        ));
    }

    public function preRemove(Produit $produit, PreRemoveEventArgs $event): void
    {
        // Stocker les données avant la suppression
        $this->removedProductData[spl_object_id($produit)] = [
            'id' => $produit->getId(),
            'nom' => $produit->getName()
        ];
    }

    public function postRemove(Produit $produit, PostRemoveEventArgs $event): void
    {
        $objectId = spl_object_id($produit);
        $productData = $this->removedProductData[$objectId] ?? null;

        if ($productData) {
            $this->bus->dispatch(new NotificationMessage(
                'SUPPRESSION',
                'Produit: ' . $productData['nom'],
                $productData['id']
            ));

            // Nettoyer les données stockées
            unset($this->removedProductData[$objectId]);
        }
    }

    public function preUpdateUser(User $user, PreUpdateEventArgs $event): void
    {
        // Vérifier si le champ 'actif' a changé
        if ($event->hasChangedField('active') && !$user->isActive()) {
            $this->userWasDeactivated = true;
        }
    }

    public function postUpdateUser(User $user, PostUpdateEventArgs $event): void
    {
        // Si l'utilisateur a été désactivé, créer une notification
        if ($this->userWasDeactivated) {
            $notification = new \App\Entity\Notification();
            $notification->setLabel('Votre compte a été désactivé - ' . (new \DateTime())->format('d/m/Y H:i:s'));
            $notification->setUser($user);

            $em = $event->getObjectManager();
            $em->persist($notification);
            $em->flush();

            $this->userWasDeactivated = false;
        }
    }
}
