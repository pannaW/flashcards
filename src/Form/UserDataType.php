<?php
/**
 * UserDataType
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints as CustomAssert;

/**
 * Class UserDataType
 * @package Form
 */
class UserDataType extends AbstractType
{
    /**
     *{@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
          'id',
            HiddenType::class
        );

        $builder->add(
            'users_id',
            HiddenType::class
        );
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => 'label.name',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['user-data-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['user-data-default'],
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                    new Assert\Regex(
                        [
                            'pattern' => '/\d/',
                            'match' => false,
                            'message' => 'Your name cannot contain a number',
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'surname',
            TextType::class,
            [
                'label' => 'label.surname',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(
                        ['groups' => ['user-data-default']]
                    ),
                    new Assert\Length(
                        [
                            'groups' => ['user-data-default'],
                            'min' => 3,
                            'max' => 45,
                        ]
                    ),
                    new Assert\Regex(
                        [
                            'pattern' => '/\d/',
                            'match' => false,
                            'message' => 'Your surname cannot contain a number',
                        ]
                    ),
                ],
            ]
        );
        $builder->add(
            'email',
            EmailType::class,
            [
                'label' => 'label.email',
                'required' => true,
                //                'constraints' => [
                //                    new CustomAssert\UniqueEmail(
                //                        //TODO: implement validators, remember about user_data_repository
                //                        [
                //                            'groups' => ['register-default'],
                //                            'repository' => isset($options['user_repository']) ? $options['user_repository'] : null,
                //                            'elementId' => isset($options['data']['id']) ? $options['data']['id'] : null,
                //                        ]
                //                    ),
                //                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'user_type';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'user-data-default',
            ]
        );
    }
}