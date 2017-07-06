<?php
/**
 * SetType
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints as CustomAssert;

/**
 * Class SetType
 * @package Form
 */
class SetType extends AbstractType
{
    /**
     *{@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add(
              'name',
              TextType::class,
              [
                  'label' => 'label.item_name',
                  'required' => true,
                  'attr' => [
                      'max_length' => 45,
                    ],
                  'constraints' => [
                      new Assert\NotBlank(
                          ['groups' => ['set-default']]
                      ),
                      new Assert\Length(
                          [
                              'groups' => ['set-default'],
                              'min' => 2,
                              'max' => 45,
                          ]
                      ),
                      new Assert\Type(
                          [
                              'type' => 'string',
                          ]
                      ),
                      new Assert\Regex(
                          [
                              'pattern' => '/\d/',
                              'match' => false,
                              'message' => 'Your name cannot contain a number',
                          ]
                      ),
                      new CustomAssert\UniqueSet(
                          [
                              'groups' => ['set-default'],
                              'repository' => isset($options['set_repository']) ? $options['set_repository'] : null,
                              'elementId' => isset($options['data']['id']) ? $options['data']['id'] : null,
                              'userId' => isset($options['data']['users_id']) ? $options['data']['users_id'] :
                                  (isset($options['userId']) ? $options['userId'] : null )
                          ]
                      ),
                  ],
              ]
          )
        ->add(
            'public',
            CheckboxType::class,
            [
                'label'    => 'label.public',
                'required' => false,
                'value' => true,
            ]
        );
        $builder->add(
            'tags',
            TextType::class,
            [
                'label'    => 'label.tags',
                'required' => false,
                'attr' => [
                    'max_length' => 250,
                ],
            ]
        );

        $builder->get('tags')->addModelTransformer(
            new TagsDataTransformer($options['tag_repository'])
        );
        $builder->get('public')->addModelTransformer(
            new SetsDataTransformer($options['set_repository'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'set_type';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => 'set-default',
                'set_repository' => null,
                'tag_repository' => null,
                'userId' => null,
            ]
        );
    }

    /**
     * @param $tagRepository
     * @return array
     */
    protected function prepareTagsForChoices($tagRepository)
    {
        $tags = $tagRepository->findAll();
        $choices = [];

        foreach ($tags as $tag) {
            $choices[$tag['name']] = $tag['id'];
        }

        return $choices;
    }
}
