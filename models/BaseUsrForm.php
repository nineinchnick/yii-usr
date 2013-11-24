<?php

/**
 * BaseUsrForm class.
 * BaseUsrForm is the base class for forms extensible using behaviors, which can add attributes and rules.
 */
abstract class BaseUsrForm extends CFormModel
{
	private static $_names=array();

	public function attributeNames()
	{
		$className=get_class($this);
		if(!isset(self::$_names[$className]))
		{
			$class=new ReflectionClass(get_class($this));
			$names=array();
			foreach($class->getProperties() as $property)
			{
				$name=$property->getName();
				if($property->isPublic() && !$property->isStatic())
					$names[]=$name;
			}
			foreach($this->behaviors() as $name=>$options) {
				if (($behavior=$this->asa($name)) instanceof FormModelBehavior)
					$names = array_merge($names, $behavior->attributeNames());
			}
			return self::$_names[$className]=array_merge($this->attributeNames(), $names);
		}
		else
			return self::$_names[$className];
	}

	public function getBehaviorLabels()
	{
		$labels = array();
		foreach($this->behaviors() as $name=>$options) {
			if (($behavior=$this->asa($name)) instanceof FormModelBehavior)
				$labels = array_merge($labels, $behavior->attributeLabels());
		}
		return $labels;
	}

	public function getBehaviorRules()
	{
		$rules = array();
		foreach($this->behaviors() as $name=>$options) {
			if (($behavior=$this->asa($name)) instanceof FormModelBehavior)
				$rules = array_merge($rules, $behavior->rules());
		}
		return $rules;
	}
}
