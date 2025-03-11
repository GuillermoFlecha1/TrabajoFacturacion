<?php

namespace Drupal\custom_user_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Clase que implementa el formulario para crear nuevos usuarios.
 */
class CustomUserForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'custom_user_form';
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
    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $dni = strtoupper(trim($form_state->getValue('dni')));

        // El DNI debe tener exactamente 9 caracteres (8 números y 1 letra).
        if (strlen($dni) !== 9) {
            $form_state->setErrorByName('dni', $this->t('El DNI debe tener 9 caracteres: 8 números y 1 letra.'));
            return;
        }

        // Extraer la parte numérica y la letra.
        $numero = substr($dni, 0, 8);
        $letra = substr($dni, 8, 1);

        // Validar que los primeros 8 caracteres sean números.
        if (!ctype_digit($numero)) {
            $form_state->setErrorByName('dni', $this->t('Los primeros 8 caracteres del DNI deben ser números.'));
            return;
        }

        // Validar que el último carácter sea una letra.
        if (!ctype_alpha($letra)) {
            $form_state->setErrorByName('dni', $this->t('El último carácter del DNI debe ser una letra.'));
            return;
        }

        // Calcular la letra correcta según el algoritmo español.
        $letras = "TRWAGMYFPDXBNJZSQVHLCKE";
        $pos = intval($numero) % 23;
        $letra_correcta = $letras[$pos];

        if ($letra !== $letra_correcta) {
            $form_state->setErrorByName('dni', $this->t('El DNI no es válido. La letra debe ser @letra_correcta.', ['@letra_correcta' => $letra_correcta]));
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
        // Asegúrate de que el nombre de máquina del campo sea el correcto (por ejemplo, 'field_dni').
        $user->set('field_dni', $form_state->getValue('dni'));

        // Guardar el usuario.
        $user->save();

        // Mensaje de confirmación usando getAccountName() en lugar de getUsername().
        $this->messenger()->addStatus($this->t('El usuario %name ha sido creado.', [
            '%name' => $user->getAccountName(),
        ]));
    }
}
