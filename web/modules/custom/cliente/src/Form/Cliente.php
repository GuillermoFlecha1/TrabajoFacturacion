<?php

namespace Drupal\cliente\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Clase que implementa el formulario para crear nuevos usuarios con roles.
 */
class Cliente extends FormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'cliente';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        // Campo para el nombre de usuario.
        $form['username'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre de usuario'),
            '#required' => TRUE,
        ];

        // Campo para el email.
        $form['mail'] = [
            '#type' => 'email',
            '#title' => $this->t('Correo electrónico'),
            '#required' => TRUE,
        ];

        // Campo para la contraseña con confirmación.
        $form['pass'] = [
            '#type' => 'password_confirm',
            '#size' => 25,
            '#required' => TRUE,
        ];

        // Campo personalizado para el DNI.
        $form['dni'] = [
            '#type' => 'textfield',
            '#title' => $this->t('DNI'),
            '#required' => TRUE,
        ];

        // Obtener todos los roles excepto "anonymous" y "authenticated".
        $roles = array_filter(\Drupal\user\Entity\Role::loadMultiple(), function ($role) {
            return $role->id() !== RoleInterface::ANONYMOUS_ID && $role->id() !== RoleInterface::AUTHENTICATED_ID;
        });
        $roles = array_map(function ($role) {
            return $role->label();
        }, $roles);

        // Campo para seleccionar el rol.
        $form['role'] = [
            '#type' => 'select',
            '#title' => $this->t('Rol de usuario'),
            '#options' => $roles,
            '#required' => TRUE,
        ];

        // Botón de envío.
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Crear usuario'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $dni = strtoupper(trim($form_state->getValue('dni')));

        // Validar formato del DNI.
        if (strlen($dni) !== 9) {
            $form_state->setErrorByName('dni', $this->t('El DNI debe tener 9 caracteres: 8 números y 1 letra.'));
            return;
        }

        $numero = substr($dni, 0, 8);
        $letra = substr($dni, 8, 1);

        if (!ctype_digit($numero)) {
            $form_state->setErrorByName('dni', $this->t('Los primeros 8 caracteres del DNI deben ser números.'));
            return;
        }

        if (!ctype_alpha($letra)) {
            $form_state->setErrorByName('dni', $this->t('El último carácter del DNI debe ser una letra.'));
            return;
        }

        // Comprobar la letra correcta.
        $letras = "TRWAGMYFPDXBNJZSQVHLCKE";
        $pos = intval($numero) % 23;
        $letra_correcta = $letras[$pos];

        if ($letra !== $letra_correcta) {
            $form_state->setErrorByName('dni', $this->t('El DNI no es válido. La letra debe ser @letra_correcta.', [
                '@letra_correcta' => $letra_correcta,
            ]));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // Crear una nueva entidad de usuario.
        $user = User::create();

        // Asignar los valores del formulario.
        $user->setUsername($form_state->getValue('username'));
        $user->setEmail($form_state->getValue('mail'));
        $user->setPassword($form_state->getValue('pass'));

        // Activar el usuario de inmediato.
        $user->activate();

        // Guardar el valor del campo DNI.
        $user->set('field_dni', $form_state->getValue('dni'));

        // Asignar el rol seleccionado.
        $selected_role = $form_state->getValue('role');
        if ($selected_role) {
            $user->addRole($selected_role);
        }

        // Guardar el usuario.
        $user->save();

        // Mensaje de confirmación.
        $this->messenger()->addStatus($this->t('El usuario %name ha sido creado con el rol %role.', [
            '%name' => $user->getAccountName(),
            '%role' => $selected_role,
        ]));
    }
}