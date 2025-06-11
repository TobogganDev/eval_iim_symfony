<?php

namespace App\EventListener;

use App\Entity\Produit;
use App\Entity\User;
use App\Message\NotificationMessage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Produit::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Produit::class)]
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: Produit::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdateUser', entity: User::class)]
class EntityChangeListener
{
    public function __construct(
        private MessageBusInterface $bus
    ) {
    }

    public function postPersist(Produit $produit, PostPersistEventArgs $event): void
    {
        $this->bus->dispatch(new NotificationMessage(
            'CREATION',
            'Produit: ' . $produit->getNom(),
            $produit->getId()
        ));
    }

    public function postUpdate(Produit $produit, PostUpdateEventArgs $event): void
    {
        $this->bus->dispatch(new NotificationMessage(
            'MODIFICATION',
            'Produit: ' . $produit->getNom(),
            $produit->getId()
        ));
    }

    public function postRemove(Produit $produit, PostRemoveEventArgs $event): void
    {
        $this->bus->dispatch(new NotificationMessage(
            'SUPPRESSION',
            'Produit: ' . $produit->getNom(),
            $produit->getId()
        ));
    }

    public function postUpdateUser(User $user, PostUpdateEventArgs $event): void
    {
        // Vérifier si le champ 'actif' a changé
        if ($event->hasChangedField('actif') && !$user->isActif()) {
            // Créer une notification pour l'utilisateur désactivé
            $notification = new \App\Entity\Notification();
            $notification->setLabel('Votre compte a été désactivé - ' . (new \DateTime())->format('d/m/Y H:i:s'));
            $notification->setUser($user);

            $em = $event->getObjectManager();
            $em->persist($notification);
            $em->flush();
        }
    }
}
